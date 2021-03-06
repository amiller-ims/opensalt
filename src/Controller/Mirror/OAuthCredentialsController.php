<?php

namespace App\Controller\Mirror;

use App\Entity\Framework\Mirror\OAuthCredential;
use App\Form\DTO\OAuthCredentialDTO;
use App\Form\Type\OAuthCredentialDTOType;
use App\Repository\Framework\Mirror\OAuthCredentialRepository;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Security("is_granted('manage', 'mirrors')")
 * @Route("/admin/mirror/credentials")
 */
class OAuthCredentialsController extends AbstractController
{
    /**
     * @Route("/", name="oauth_credentials_index", methods={"GET"})
     */
    public function index(OAuthCredentialRepository $oAuthCredentialRepository): Response
    {
        return $this->render('mirror/oauth_credentials/index.html.twig', [
            'oauth_credentials' => $oAuthCredentialRepository->findAll(),
        ]);
    }

    /**
     * @Route("/new", name="oauth_credentials_new", methods={"GET","POST"})
     */
    public function new(Request $request): Response
    {
        $oAuthCredentialDto = new OAuthCredentialDTO();
        $form = $this->createForm(OAuthCredentialDTOType::class, $oAuthCredentialDto);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $oAuthCredential = new OAuthCredential();
            $oAuthCredential->setAuthenticationEndpoint($oAuthCredentialDto->authenticationEndpoint);
            $oAuthCredential->setKey($oAuthCredentialDto->key);
            $oAuthCredential->setSecret($oAuthCredentialDto->secret);

            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($oAuthCredential);
            $entityManager->flush();

            return $this->redirectToRoute('oauth_credentials_index');
        }

        return $this->render('mirror/oauth_credentials/new.html.twig', [
            'oauth_credentials' => $oAuthCredentialDto,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}", name="oauth_credentials_show", methods={"GET"})
     */
    public function show(OAuthCredential $oAuthCredential): Response
    {
        return $this->render('mirror/oauth_credentials/show.html.twig', [
            'oauth_credentials' => $oAuthCredential,
        ]);
    }

    /**
     * @Route("/{id}/edit", name="oauth_credentials_edit", methods={"GET","POST"})
     */
    public function edit(Request $request, OAuthCredential $oAuthCredential): Response
    {
        $dto = new OAuthCredentialDTO();
        $dto->authenticationEndpoint = $oAuthCredential->getAuthenticationEndpoint();
        $dto->key = $oAuthCredential->getKey();
        $dto->secret = $oAuthCredential->getSecret();

        $form = $this->createForm(OAuthCredentialDTOType::class, $dto);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $oAuthCredential->setAuthenticationEndpoint($dto->authenticationEndpoint);
            $oAuthCredential->setKey($dto->key);
            $oAuthCredential->setSecret($dto->secret);
            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute('oauth_credentials_index');
        }

        return $this->render('mirror/oauth_credentials/edit.html.twig', [
            'oauth_credentials' => $oAuthCredential,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}", name="oauth_credentials_delete", methods={"DELETE"})
     */
    public function delete(Request $request, OAuthCredential $oAuthCredential): Response
    {
        if ($this->isCsrfTokenValid('delete'.$oAuthCredential->getId(), $request->request->get('_token'))) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($oAuthCredential);
            $entityManager->flush();
        }

        return $this->redirectToRoute('oauth_credentials_index');
    }
}
