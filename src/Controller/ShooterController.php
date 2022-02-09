<?php

namespace App\Controller;

use App\Entity\Shooter;
use App\Form\Type\ShooterType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ShooterController extends AbstractController
{
    /**
     * @Route("/admin/shooter", name="admin_shooter", methods={"GET"})
     * @param EntityManagerInterface $entityManager
     * @return Response
     */
    public function indexAction(EntityManagerInterface $entityManager)
    {
        $entities = $entityManager->getRepository(Shooter::class)->findAll();
        return $this->render('Shooter/index.html.twig', [ 'entities' => $entities ]);
    }

    /**
     * @Route("/admin/shooter/create", name="admin_shooter_create", methods={"POST"})
     * @param EntityManagerInterface $entityManager
     * @param Request $request
     * @return RedirectResponse|Response
     */
    public function createAction(EntityManagerInterface $entityManager, Request $request)
    {
        $entity = new Shooter();
        $form = $this->createCreateForm($entity);
        $form->handleRequest($request);
        if ($form->isValid()) {
            $entityManager->persist($entity);
            $entityManager->flush();
            return $this->redirect($this->generateUrl('admin_shooter_show', ['id' => $entity->getId()]));
        }
        return $this->render('Shooter/new.html.twig', [
            'entity' => $entity,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/admin/shooter/new", name="admin_shooter_new", methods={"GET"})
     * @return Response
     */
    public function newAction()
    {
        $entity = new Shooter();
        $form   = $this->createCreateForm($entity);
        return $this->render('Shooter/new.html.twig', [
            'entity' => $entity,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/admin/shooter/{id}/show", name="admin_shooter_show", methods={"GET"})
     * @param EntityManagerInterface $entityManager
     * @param $id
     * @return Response
     */
    public function showAction(EntityManagerInterface $entityManager, $id)
    {
        $entity = $entityManager->getRepository(Shooter::class)->find($id);
        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Shooter entity.');
        }
        $deleteForm = $this->createDeleteForm($id);
        return $this->render('Shooter/show.html.twig', [
            'entity' => $entity,
            'delete_form' => $deleteForm->createView(),
        ]);
    }

    /**
     * @Route("/admin/shooter/{id}/edit", name="admin_shooter_edit", methods={"GET"})
     * @param EntityManagerInterface $entityManager
     * @param $id
     * @return Response
     */
    public function editAction(EntityManagerInterface $entityManager, $id)
    {
        /* @var Shooter $entity */
        $entity = $entityManager->getRepository(Shooter::class)->find($id);
        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Shooter entity.');
        }
        $editForm = $this->createEditForm($entity);
        $deleteForm = $this->createDeleteForm($id);
        return $this->render('Shooter/edit.html.twig', [
            'entity' => $entity,
            'edit_form' => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ]);
    }

    /**
     * @Route("/admin/shooter/{id}/update", name="admin_shooter_update", methods={"POST", "PUT"})
     * @param EntityManagerInterface $entityManager
     * @param Request $request
     * @param $id
     * @return RedirectResponse|Response
     */
    public function updateAction(EntityManagerInterface $entityManager, Request $request, $id)
    {
        /* @var Shooter $entity */
        $entity = $entityManager->getRepository(Shooter::class)->find($id);
        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Shooter entity.');
        }
        $deleteForm = $this->createDeleteForm($id);
        $editForm = $this->createEditForm($entity);
        $editForm->handleRequest($request);
        if ($editForm->isValid()) {
            $entityManager->flush();
            return $this->redirect($this->generateUrl('admin_shooter_edit', ['id' => $id]));
        }
        return $this->render('Shooter/edit.html.twig', [
            'entity' => $entity,
            'edit_form' => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ]);
    }

    /**
     * @Route("/admin/shooter/{id}/delete", name="admin_shooter_delete", methods={"POST", "DELETE"})
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
            $entity = $entityManager->getRepository(Shooter::class)->find($id);
            if (!$entity) {
                throw $this->createNotFoundException('Unable to find Shooter entity.');
            }
            $entityManager->remove($entity);
            $entityManager->flush();
        }
        return $this->redirect($this->generateUrl('admin_shooter'));
    }

    /**
     * @param Shooter $entity
     * @return FormInterface
     */
    protected function createCreateForm(Shooter $entity)
    {
        $form = $this->createForm(ShooterType::class, $entity, [
            'action' => $this->generateUrl('admin_shooter_create'),
            'method' => 'POST',
        ]);
        $form->add('submit', SubmitType::class, ['label' => 'Create']);
        return $form;
    }

    /**
     * @param Shooter $entity
     * @return FormInterface
     */
    protected function createEditForm(Shooter $entity)
    {
        $form = $this->createForm(ShooterType::class, $entity, [
            'action' => $this->generateUrl('admin_shooter_update', ['id' => $entity->getId()]),
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
            ->setAction($this->generateUrl('admin_shooter_delete', ['id' => $id]))
            ->setMethod('DELETE')
            ->add('submit', SubmitType::class, ['label' => 'Delete'])
            ->getForm();
    }
}
