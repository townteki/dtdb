<?php

namespace App\Controller;

use App\Entity\Card;
use App\Form\Type\CardType;
use App\Repository\CardRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class CardController extends AbstractController
{
    /**
     * @Route("/admin/card", name="admin_card", methods={"GET"})
     * @param EntityManagerInterface $entityManager
     * @return Response
     */
    public function indexAction(EntityManagerInterface $entityManager)
    {
        $entities = $entityManager->getRepository(Card::class)->findAll();
        return $this->render('Card/index.html.twig', [
            'entities' => $entities,
        ]);
    }

    /**
     * @Route("/admin/card/create", name="admin_card_create", methods={"POST"})
     * @param EntityManagerInterface $entityManager
     * @param CardRepository $repository
     * @param Request $request
     * @return RedirectResponse|Response
     */
    public function createAction(EntityManagerInterface $entityManager, CardRepository $repository, Request $request)
    {
        $entity = new Card();
        $form = $this->createCreateForm($entity);
        $form->handleRequest($request);
        if ($form->isValid()) {
            $entityManager->persist($entity);
            $entityManager->flush();
            $repository->updateIsMultipleFlagOnAllCards();
            return $this->redirect($this->generateUrl('admin_card_show', ['id' => $entity->getId()]));
        }
        return $this->render('Card/new.html.twig', [
            'entity' => $entity,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/admin/card/new", name="admin_card_new", methods={"GET"})
     * @return Response
     */
    public function newAction()
    {
        $entity = new Card();
        $form = $this->createCreateForm($entity);
        return $this->render('Card/new.html.twig', [
            'entity' => $entity,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/admin/card/{id}/show", name="admin_card_show", methods={"GET"})
     * @return Response
     */
    public function showAction(EntityManagerInterface $entityManager, $id)
    {
        $entity = $entityManager->getRepository(Card::class)->find($id);
        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Card entity.');
        }
        $deleteForm = $this->createDeleteForm($id);
        return $this->render('Card/show.html.twig', [
            'entity' => $entity,
            'delete_form' => $deleteForm->createView(),
        ]);
    }

    /**
     * @Route("/admin/card/{id}/edit", name="admin_card_edit", methods={"GET"})
     * @param EntityManagerInterface $entityManager
     * @param $id
     * @return Response
     */
    public function editAction(EntityManagerInterface $entityManager, $id)
    {
        /* @var Card $entity */
        $entity = $entityManager->getRepository(Card::class)->find($id);
        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Card entity.');
        }
        $editForm = $this->createEditForm($entity);
        $deleteForm = $this->createDeleteForm($id);
        return $this->render('Card/edit.html.twig', [
            'entity' => $entity,
            'edit_form' => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ]);
    }

    /**
     * @Route("/admin/card/{id}/update", name="admin_card_update", methods={"POST", "PUT"})
     * @param EntityManagerInterface $entityManager
     * @param CardRepository $repository
     * @param Request $request
     * @param $id
     * @return RedirectResponse|Response
     */
    public function updateAction(
        EntityManagerInterface $entityManager,
        CardRepository $repository,
        Request $request,
        $id
    ) {
        /* @var Card $entity */
        $entity = $entityManager->getRepository(Card::class)->find($id);
        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Card entity.');
        }
        $deleteForm = $this->createDeleteForm($id);
        $editForm = $this->createEditForm($entity);
        $editForm->handleRequest($request);
        if ($editForm->isValid()) {
            $entityManager->flush();
            $repository->updateIsMultipleFlagOnAllCards();
            return $this->redirect($this->generateUrl('admin_card_edit', ['id' => $id]));
        }
        return $this->render('Card/edit.html.twig', [
            'entity' => $entity,
            'edit_form' => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ]);
    }

    /**
     * @Route("/admin/card/{id}/delete", name="admin_card_delete", methods={"POST", "DELETE"})
     * @param EntityManagerInterface $entityManager
     * @param CardRepository $repository
     * @param Request $request
     * @param $id
     * @return RedirectResponse
     */
    public function deleteAction(
        EntityManagerInterface $entityManager,
        CardRepository $repository,
        Request $request,
        $id
    ) {
        $form = $this->createDeleteForm($id);
        $form->handleRequest($request);
        if ($form->isValid()) {
            $entity = $entityManager->getRepository(Card::class)->find($id);
            if (!$entity) {
                throw $this->createNotFoundException('Unable to find Card entity.');
            }
            $entityManager->remove($entity);
            $entityManager->flush();
            $repository->updateIsMultipleFlagOnAllCards();
        }
        return $this->redirect($this->generateUrl('admin_card'));
    }

    /**
     * @param Card $entity
     * @return FormInterface
     */
    protected function createCreateForm(Card $entity)
    {
        $form = $this->createForm(CardType::class, $entity, [
            'action' => $this->generateUrl('admin_card_create'),
            'method' => 'POST',
        ]);
        $form->add('submit', SubmitType::class, ['label' => 'Create']);
        return $form;
    }

    /**
     * @param Card $entity
     * @return FormInterface
     */
    protected function createEditForm(Card $entity)
    {
        $form = $this->createForm(CardType::class, $entity, [
            'action' => $this->generateUrl('admin_card_update', ['id' => $entity->getId()]),
            'method' => 'PUT',
        ]);
        $form->add('submit', SubmitType::class, ['label' => 'Update']);
        return $form;
    }

    /**
     * @param $id
     * @return FormInterface
     */
    protected function createDeleteForm($id)
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('admin_card_delete', ['id' => $id]))
            ->setMethod('DELETE')
            ->add('submit', SubmitType::class, ['label' => 'Delete'])
            ->getForm();
    }
}
