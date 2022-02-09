<?php

namespace App\Controller;

use App\Entity\Suit;
use App\Form\Type\SuitType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class SuitController extends AbstractController
{
    /**
     * @Route("/admin/suit", name="admin_suit", methods={"GET"})
     * @param EntityManagerInterface $entityManager
     * @return Response
     */
    public function indexAction(EntityManagerInterface $entityManager)
    {
        $entities = $entityManager->getRepository(Suit::class)->findAll();
        return $this->render('Suit/index.html.twig', [
            'entities' => $entities,
        ]);
    }

    /**
     * @Route("/admin/suit/create", name="admin_suit_create", methods={"POST"})
     * @param EntityManagerInterface $entityManager
     * @param Request $request
     * @return RedirectResponse|Response
     */
    public function createAction(EntityManagerInterface $entityManager, Request $request)
    {
        $entity = new Suit();
        $form = $this->createCreateForm($entity);
        $form->handleRequest($request);
        if ($form->isValid()) {
            $entityManager->persist($entity);
            $entityManager->flush();
            return $this->redirect($this->generateUrl('admin_suit_show', ['id' => $entity->getId()]));
        }
        return $this->render('Suit/new.html.twig', [
            'entity' => $entity,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/admin/suit/new", name="admin_suit_new", methods={"GET"})
     * @return Response
     */
    public function newAction()
    {
        $entity = new Suit();
        $form = $this->createCreateForm($entity);
        return $this->render('Suit/new.html.twig', [
            'entity' => $entity,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/admin/suit/{id}/show", name="admin_suit_show", methods={"GET"})
     * @param EntityManagerInterface $entityManager
     * @param $id
     * @return Response
     */
    public function showAction(EntityManagerInterface $entityManager, $id)
    {
        $entity = $entityManager->getRepository(Suit::class)->find($id);
        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Suit entity.');
        }
        $deleteForm = $this->createDeleteForm($id);
        return $this->render('Suit/show.html.twig', [
            'entity' => $entity,
            'delete_form' => $deleteForm->createView(),
        ]);
    }

    /**
     * @Route("/admin/suit/{id}/edit", name="admin_suit_edit", methods={"GET"})
     * @param EntityManagerInterface $entityManager
     * @param $id
     * @return Response
     */
    public function editAction(EntityManagerInterface $entityManager, $id)
    {
        /* @var Suit $entity */
        $entity = $entityManager->getRepository(Suit::class)->find($id);
        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Suit entity.');
        }
        $editForm = $this->createEditForm($entity);
        $deleteForm = $this->createDeleteForm($id);
        return $this->render('Suit/edit.html.twig', [
            'entity' => $entity,
            'edit_form' => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ]);
    }

    /**
     * @Route("/admin/suit/{id}/update", name="admin_suit_update", methods={"POST", "PUT"})
     * @param EntityManagerInterface $entityManager
     * @param Request $request
     * @param $id
     * @return RedirectResponse|Response
     */
    public function updateAction(EntityManagerInterface $entityManager, Request $request, $id)
    {
        /* @var Suit $entity */
        $entity = $entityManager->getRepository(Suit::class)->find($id);
        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Suit entity.');
        }
        $deleteForm = $this->createDeleteForm($id);
        $editForm = $this->createEditForm($entity);
        $editForm->handleRequest($request);
        if ($editForm->isValid()) {
            $entityManager->flush();
            return $this->redirect($this->generateUrl('admin_suit_edit', ['id' => $id]));
        }
        return $this->render('Suit/edit.html.twig', [
            'entity' => $entity,
            'edit_form' => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ]);
    }

    /**
     * @Route("/admin/suit/{id}/delete", name="admin_suit_delete", methods={"POST", "DELETE"})
     * @param EntityManagerInterface $entityManager
     * @param Request $request
     * @param $id
     * @return RedirectResponse
     */
    public function deleteAction(EntityManagerInterface $entityManager, Request $request, $id)
    {
        $form = $this->createDeleteForm($id);
        $form->handleRequest($request);
        if ($form->isValid()) {
            $entity = $entityManager->getRepository(Suit::class)->find($id);
            if (!$entity) {
                throw $this->createNotFoundException('Unable to find Suit entity.');
            }
            $entityManager->remove($entity);
            $entityManager->flush();
        }
        return $this->redirect($this->generateUrl('admin_suit'));
    }

    /**
     * @param Suit $entity
     * @return FormInterface
     */
    protected function createCreateForm(Suit $entity)
    {
        $form = $this->createForm(SuitType::class, $entity, [
            'action' => $this->generateUrl('admin_suit_create'),
            'method' => 'POST',
        ]);
        $form->add('submit', SubmitType::class, ['label' => 'Create']);
        return $form;
    }

    /**
     * @param Suit $entity
     * @return FormInterface
     */
    protected function createEditForm(Suit $entity)
    {
        $form = $this->createForm(SuitType::class, $entity, [
            'action' => $this->generateUrl('admin_suit_update', ['id' => $entity->getId()]),
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
            ->setAction($this->generateUrl('admin_suit_delete', ['id' => $id]))
            ->setMethod('DELETE')
            ->add('submit', SubmitType::class, ['label' => 'Delete'])
            ->getForm();
    }
}
