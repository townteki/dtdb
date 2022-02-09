<?php

namespace App\Controller;

use App\Entity\Type;
use App\Form\Type\TypeType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class TypeController extends AbstractController
{
    /**
     * @Route("/admin/type", name="admin_type", methods={"GET"})
     * @param EntityManagerInterface $entityManager
     * @return Response
     */
    public function indexAction(EntityManagerInterface $entityManager)
    {
        $entities = $entityManager->getRepository(Type::class)->findAll();
        return $this->render('Type/index.html.twig', [
            'entities' => $entities,
        ]);
    }

    /**
     * @Route("/admin/type/create", name="admin_type_create", methods={"POST"})
     * @param EntityManagerInterface $entityManager
     * @param Request $request
     * @return RedirectResponse|Response
     */
    public function createAction(EntityManagerInterface $entityManager, Request $request)
    {
        $entity = new Type();
        $form = $this->createCreateForm($entity);
        $form->handleRequest($request);
        if ($form->isValid()) {
            $entityManager->persist($entity);
            $entityManager->flush();
            return $this->redirect($this->generateUrl('admin_type_show', ['id' => $entity->getId()]));
        }
        return $this->render('Type/new.html.twig', [
            'entity' => $entity,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/admin/type/new", name="admin_type_new", methods={"GET"})
     * @return Response
     */
    public function newAction()
    {
        $entity = new Type();
        $form   = $this->createCreateForm($entity);
        return $this->render('Type/new.html.twig', [
            'entity' => $entity,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/admin/type/{id}/show", name="admin_type_show", methods={"GET"})
     * @param EntityManagerInterface $entityManager
     * @param $id
     * @return Response
     */
    public function showAction(EntityManagerInterface $entityManager, $id)
    {
        $entity = $entityManager->getRepository(Type::class)->find($id);
        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Type entity.');
        }
        $deleteForm = $this->createDeleteForm($id);
        return $this->render('Type/show.html.twig', [
            'entity' => $entity,
            'delete_form' => $deleteForm->createView(),
        ]);
    }

    /**
     * @Route("/admin/type/{id}/edit", name="admin_type_edit", methods={"GET"})
     * @param EntityManagerInterface $entityManager
     * @param $id
     * @return Response
     */
    public function editAction(EntityManagerInterface $entityManager, $id)
    {
        /* @var Type $entity */
        $entity = $entityManager->getRepository(Type::class)->find($id);
        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Type entity.');
        }
        $editForm = $this->createEditForm($entity);
        $deleteForm = $this->createDeleteForm($id);
        return $this->render('Type/edit.html.twig', [
            'entity' => $entity,
            'edit_form' => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ]);
    }

    /**
     * @Route("/admin/type/{id}/update", name="admin_type_update", methods={"POST", "PUT"})
     * @param EntityManagerInterface $entityManager
     * @param Request $request
     * @param $id
     * @return RedirectResponse|Response
     */
    public function updateAction(EntityManagerInterface $entityManager, Request $request, $id)
    {
        /* @var Type $entity */
        $entity = $entityManager->getRepository(Type::class)->find($id);
        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Type entity.');
        }
        $deleteForm = $this->createDeleteForm($id);
        $editForm = $this->createEditForm($entity);
        $editForm->handleRequest($request);
        if ($editForm->isValid()) {
            $entityManager->flush();
            return $this->redirect($this->generateUrl('admin_type_edit', ['id' => $id]));
        }
        return $this->render('Type/edit.html.twig', [
            'entity' => $entity,
            'edit_form' => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ]);
    }

    /**
     * @Route("/admin/type/{id}/delete", name="admin_type_delete", methods={"POST", "DELETE"})
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
            $entity = $entityManager->getRepository(Type::class)->find($id);
            if (!$entity) {
                throw $this->createNotFoundException('Unable to find Type entity.');
            }
            $entityManager->remove($entity);
            $entityManager->flush();
        }
        return $this->redirect($this->generateUrl('admin_type'));
    }

    /**
     * @param Type $entity
     * @return FormInterface
     */
    protected function createCreateForm(Type $entity)
    {
        $form = $this->createForm(TypeType::class, $entity, [
            'action' => $this->generateUrl('admin_type_create'),
            'method' => 'POST',
        ]);
        $form->add('submit', SubmitType::class, ['label' => 'Create']);
        return $form;
    }

    /**
     * @param Type $entity
     * @return FormInterface
     */
    protected function createEditForm(Type $entity)
    {
        $form = $this->createForm(TypeType::class, $entity, [
            'action' => $this->generateUrl('admin_type_update', ['id' => $entity->getId()]),
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
            ->setAction($this->generateUrl('admin_type_delete', ['id' => $id]))
            ->setMethod('DELETE')
            ->add('submit', SubmitType::class, ['label' => 'Delete'])
            ->getForm();
    }
}
