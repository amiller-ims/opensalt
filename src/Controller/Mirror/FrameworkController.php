<?php

namespace App\Controller\Mirror;

use App\Entity\Framework\LsDoc;
use App\Entity\Framework\Mirror\Framework;
use App\Entity\Framework\Mirror\Log;
use App\Form\DTO\MirroredFrameworkDTO;
use App\Form\Type\MirroredFrameworkDTOType;
use App\Service\MirrorServer;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Security("is_granted('manage', 'mirrors')")
 * @Route("/admin/mirror/framework")
 */
class FrameworkController extends AbstractController
{
    /**
     * @Route("/new", name="mirror_framework_new")
     */
    public function new(Request $request, MirrorServer $mirrorService): Response
    {
        $frameworkDto = new MirroredFrameworkDTO();
        $form = $this->createForm(MirroredFrameworkDTOType::class, $frameworkDto);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $framework = $mirrorService->addSingleFramework($frameworkDto);
                $server = $framework->getServer();

                return $this->redirectToRoute('mirror_server_list', ['id' => $server->getId()]);
            } catch (\Exception $e) {
                $this->addFlash('error', $e->getMessage());
            }
        }

        return $this->render('mirror/framework/new.html.twig', [
            'mirrored_framework' => $frameworkDto,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}/resolve-id-conflict", name="mirror_framework_resolve_conflict")
     */
    public function resolveConflict(Request $request, Framework $framework): Response
    {
        $em = $this->getDoctrine()->getManager();

        $doc = $em->getRepository(LsDoc::class)->findOneByIdentifier($framework->getIdentifier());
        if (null === $doc) {
            $this->addFlash('error', 'There is no conflict.');

            return $this->redirectToRoute('mirror_server_list', ['id' => $framework->getServer()->getId()]);
        }

        $resolveForm = $this->createFrameworkResolveForm($framework);
        $resolveForm->handleRequest($request);

        if ($resolveForm->isSubmitted() && $resolveForm->isValid()) {
            $prevFramework = $doc->getMirroredFramework();
            if (null === $prevFramework) {
                $doc->setMirroredFramework($framework);
            } else {
                $doc->setOrg(null);
                $doc->setUser(null);
                $doc->setMirroredFramework($framework);
                $prevFramework->markFailure(Framework::ERROR_ID_CONFLICT);
                $prevFramework->setInclude(false);
                $prevFramework->addLog(Log::STATUS_FAILURE, 'A framework already exists on the server with the same identifier');
            }

            $framework->setInclude(true);
            $framework->markToRefresh();

            foreach ($doc->getDocAcls() as $acl) {
                $em->remove($acl);
            }

            $em->flush();

            return $this->redirectToRoute('mirror_server_list', ['id' => $framework->getServer()->getId()]);
        }

        return $this->render('mirror/framework/resolve.html.twig', [
            'frameworkToMirror' => $framework,
            'currentFramework' => $doc,
            'resolveForm' => $resolveForm->createView(),
        ]);
    }

    /**
     * @Route("/{id}/refresh", methods={"POST"}, name="mirror_framework_refresh")
     */
    public function refresh(Framework $framework): Response
    {
        $framework->markToRefresh();
        $this->getDoctrine()->getManager()->flush();

        return $this->redirectToRoute('mirror_server_list', ['id' => $framework->getServer()->getId()]);
    }

    /**
     * @Route("/{id}/enable", methods={"POST"}, name="mirror_framework_enable")
     */
    public function enable(Framework $framework): Response
    {
        $framework->setInclude(true);
        $framework->markToRefresh();
        $this->getDoctrine()->getManager()->flush();

        return $this->redirectToRoute('mirror_server_list', ['id' => $framework->getServer()->getId()]);
    }

    /**
     * @Route("/{id}/disable", methods={"POST"}, name="mirror_framework_disable")
     */
    public function disable(Framework $framework): Response
    {
        $framework->setInclude(false);
        $this->getDoctrine()->getManager()->flush();

        return $this->redirectToRoute('mirror_server_list', ['id' => $framework->getServer()->getId()]);
    }

    /**
     * @Route("/{id}/logs", name="mirror_framework_logs")
     */
    public function viewLog(Framework $framework): Response
    {
        return $this->render('mirror/framework/logs.html.twig', [
            'framework' => $framework,
        ]);
    }

    private function createFrameworkResolveForm(Framework $framework): FormInterface
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('mirror_framework_resolve_conflict', ['id' => $framework->getId()]))
            ->getForm()
            ;
    }
}
