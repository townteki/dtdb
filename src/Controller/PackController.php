<?php

namespace App\Controller;

use App\Entity\Pack;
use App\Form\Type\PackType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class PackController extends AbstractController
{
    /**
     * @Route("/admin/pack", name="admin_pack", methods={"GET"})
     * @param EntityManagerInterface $entityManager
     * @return Response
     */
    public function indexAction(EntityManagerInterface $entityManager)
    {
        $entities = $entityManager->getRepository(Pack::class)->findAll();
        return $this->render('Pack/index.html.twig', ['entities' => $entities]);
    }

    /**
     * @Route("/admin/pack/create", name="admin_pack_create", methods={"POST"})
     * @param EntityManagerInterface $entityManager
     * @param Request $request
     * @return RedirectResponse|Response
     */
    public function createAction(EntityManagerInterface $entityManager, Request $request)
    {
        $entity = new Pack();
        $form = $this->createCreateForm($entity);
        $form->handleRequest($request);
        if ($form->isValid()) {
            $entityManager->persist($entity);
            $entityManager->flush();
            return $this->redirect($this->generateUrl('admin_pack_show', ['id' => $entity->getId()]));
        }

        return $this->render('Pack/new.html.twig', [
            'entity' => $entity,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/admin/pack/new", name="admin_pack_new", methods={"GET"})
     * @return Response
     */
    public function newAction()
    {
        $entity = new Pack();
        $form = $this->createCreateForm($entity);
        return $this->render('Pack/new.html.twig', [
            'entity' => $entity,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/admin/pack/{id}/show", name="admin_pack_show", methods={"GET"})
     * @param EntityManagerInterface $entityManager
     * @param $id
     * @return Response
     */
    public function showAction(EntityManagerInterface $entityManager, $id)
    {
        $entity = $entityManager->getRepository(Pack::class)->find($id);
        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Pack entity.');
        }

        $deleteForm = $this->createDeleteForm($id);

        return $this->render('Pack/show.html.twig', [
            'entity' => $entity,
            'delete_form' => $deleteForm->createView(),
        ]);
    }

    /**
     * @Route("/admin/pack/{id}/edit", name="admin_pack_edit", methods={"GET"})
     * @param EntityManagerInterface $entityManager
     * @param $id
     * @return Response
     */
    public function editAction(EntityManagerInterface $entityManager, $id)
    {
        $entity = $entityManager->getRepository(Pack::class)->find($id);
        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Pack entity.');
        }
        $editForm = $this->createEditForm($entity);
        $deleteForm = $this->createDeleteForm($id);
        return $this->render('Pack/edit.html.twig', [
            'entity' => $entity,
            'edit_form' => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ]);
    }

    /**
     * @Route("/admin/pack/{id}/update", name="admin_pack_update", methods={"POST", "PUT"})
     * @param EntityManagerInterface $entityManager
     * @param Request $request
     * @param $id
     * @return RedirectResponse|Response
     */
    public function updateAction(EntityManagerInterface $entityManager, Request $request, $id)
    {
        $entity = $entityManager->getRepository(Pack::class)->find($id);
        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Pack entity.');
        }
        $deleteForm = $this->createDeleteForm($id);
        $editForm = $this->createEditForm($entity);
        $editForm->handleRequest($request);
        if ($editForm->isValid()) {
            $entityManager->persist($entity);
            $entityManager->flush();
            return $this->redirect($this->generateUrl('admin_pack_edit', ['id' => $id]));
        }
        return $this->render('Pack/edit.html.twig', [
            'entity' => $entity,
            'edit_form' => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ]);
    }

    /**
     * @Route("/admin/pack/{id}/delete", name="admin_pack_delete", methods={"POST", "DELETE"})
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
            $entity = $entityManager->getRepository(Pack::class)->find($id);
            if (!$entity) {
                throw $this->createNotFoundException('Unable to find Pack entity.');
            }
            $entityManager->remove($entity);
            $entityManager->flush();
        }
        return $this->redirect($this->generateUrl('admin_pack'));
    }


    /**
     * @param Pack $entity
     * @return FormInterface
     */
    protected function createCreateForm(Pack $entity)
    {
        $form = $this->createForm(PackType::class, $entity, [
            'action' => $this->generateUrl('admin_pack_create'),
            'method' => 'POST',
        ]);
        $form->add('submit', SubmitType::class, ['label' => 'Create']);
        return $form;
    }

    /**
     * @param Pack $entity
     * @return FormInterface
     */
    protected function createEditForm(Pack $entity)
    {
        $form = $this->createForm(PackType::class, $entity, [
            'action' => $this->generateUrl('admin_pack_update', ['id' => $entity->getId()]),
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
            ->setAction($this->generateUrl('admin_pack_delete', ['id' => $id]))
            ->setMethod('DELETE')
            ->add('submit', SubmitType::class, ['label' => 'Delete'])
            ->getForm();
    }
}
