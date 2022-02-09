<?php

namespace App\Controller;

use App\Entity\Gang;
use App\Form\Type\GangType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class GangController extends AbstractController
{
    /**
     * @Route("/admin/gang", name="admin_gang", methods={"GET"})
     * @param EntityManagerInterface $entityManager
     * @return Response
     */
    public function indexAction(EntityManagerInterface $entityManager)
    {
        $entities = $entityManager->getRepository(Gang::class)->findAll();
        return $this->render('Gang/index.html.twig', ['entities' => $entities]);
    }

    /**
     * @Route("/admin/gang/create", name="admin_gang_create", methods={"POST"})
     * @param EntityManagerInterface $entityManager
     * @param Request $request
     * @return RedirectResponse|Response
     */
    public function createAction(EntityManagerInterface $entityManager, Request $request)
    {
        $entity = new Gang();
        $form = $this->createCreateForm($entity);
        $form->handleRequest($request);

        if ($form->isValid()) {
            $entityManager->persist($entity);
            $entityManager->flush();
            return $this->redirect($this->generateUrl('admin_gang_show', ['id' => $entity->getId()]));
        }

        return $this->render('Gang/new.html.twig', [
            'entity' => $entity,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/admin/gang/new", name="admin_gang_new", methods={"GET"})
     * @return Response
     */
    public function newAction()
    {
        $entity = new Gang();
        $form = $this->createCreateForm($entity);

        return $this->render('Gang/new.html.twig', [
            'entity' => $entity,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/admin/gang/{id}/show", name="admin_gang_show", methods={"GET"})
     * @param EntityManagerInterface $entityManager
     * @param $id
     * @return Response
     */
    public function showAction(EntityManagerInterface $entityManager, $id)
    {
        $entity = $entityManager->getRepository(Gang::class)->find($id);
        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Gang entity.');
        }
        $deleteForm = $this->createDeleteForm($id);

        return $this->render('Gang/show.html.twig', [
            'entity' => $entity,
            'delete_form' => $deleteForm->createView(),
        ]);
    }

    /**
     * @Route("/admin/gang/{id}/edit", name="admin_gang_edit", methods={"GET"})
     * @param EntityManagerInterface $entityManager
     * @param $id
     * @return Response
     */
    public function editAction(EntityManagerInterface $entityManager, $id)
    {
        /* @var Gang $entity */
        $entity = $entityManager->getRepository(Gang::class)->find($id);
        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Gang entity.');
        }
        $editForm = $this->createEditForm($entity);
        $deleteForm = $this->createDeleteForm($id);

        return $this->render('Gang/edit.html.twig', [
            'entity' => $entity,
            'edit_form' => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ]);
    }

    /**
     * @Route("/admin/gang/{id}/update", name="admin_gang_update", methods={"POST", "PUT"})
     * @param EntityManagerInterface $entityManager
     * @param Request $request
     * @param $id
     * @return RedirectResponse|Response
     */
    public function updateAction(EntityManagerInterface $entityManager, Request $request, $id)
    {
        /* @var Gang $entity */
        $entity = $entityManager->getRepository(Gang::class)->find($id);
        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Gang entity.');
        }
        $deleteForm = $this->createDeleteForm($id);
        $editForm = $this->createEditForm($entity);
        $editForm->handleRequest($request);

        if ($editForm->isValid()) {
            $entityManager->flush();
            return $this->redirect($this->generateUrl('admin_gang_edit', ['id' => $id]));
        }

        return $this->render('Gang/edit.html.twig', [
            'entity' => $entity,
            'edit_form' => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ]);
    }

    /**
     * @Route("/admin/gang/{id}/delete", name="admin_gang_delete", methods={"POST", "DELETE"})
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
            $entity = $entityManager->getRepository(Gang::class)->find($id);
            if (!$entity) {
                throw $this->createNotFoundException('Unable to find Gang entity.');
            }
            $entityManager->remove($entity);
            $entityManager->flush();
        }

        return $this->redirect($this->generateUrl('admin_gang'));
    }


    /**
     * @param Gang $entity
     * @return FormInterface
     */
    protected function createCreateForm(Gang $entity)
    {
        $form = $this->createForm(GangType::class, $entity, [
            'action' => $this->generateUrl('admin_gang_create'),
            'method' => 'POST',
        ]);
        $form->add('submit', SubmitType::class, ['label' => 'Create']);
        return $form;
    }

    /**
     * @param Gang $entity
     * @return FormInterface
     */
    protected function createEditForm(Gang $entity)
    {
        $form = $this->createForm(GangType::class, $entity, [
            'action' => $this->generateUrl('admin_gang_update', ['id' => $entity->getId()]),
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
            ->setAction($this->generateUrl('admin_gang_delete', ['id' => $id]))
            ->setMethod('DELETE')
            ->add('submit', SubmitType::class, ['label' => 'Delete'])
            ->getForm();
    }
}
