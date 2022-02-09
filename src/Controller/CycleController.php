<?php

namespace App\Controller;

use App\Entity\Cycle;
use App\Form\Type\CycleType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class CycleController extends AbstractController
{
    /**
     * @Route("/admin/cycle", name="admin_cycle", methods={"GET"})
     * @param EntityManagerInterface $entityManager
     * @return Response
     */
    public function indexAction(EntityManagerInterface $entityManager)
    {
        $entities = $entityManager->getRepository(Cycle::class)->findAll();
        return $this->render('Cycle/index.html.twig', [
            'entities' => $entities,
        ]);
    }

    /**
     * @Route("/admin/cycle/create", name="admin_cycle_create", methods={"POST"})
     * @param EntityManagerInterface $entityManager
     * @param Request $request
     * @return RedirectResponse|Response
     */
    public function createAction(EntityManagerInterface $entityManager, Request $request)
    {
        $entity  = new Cycle();
        $form = $this->createForm(CycleType::class, $entity);
        $form->handleRequest($request);
        if ($form->isValid()) {
            $entityManager->persist($entity);
            $entityManager->flush();
            return $this->redirect($this->generateUrl('admin_cycle_show', ['id' => $entity->getId()]));
        }

        return $this->render('Cycle/new.html.twig', [
            'entity' => $entity,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/admin/cycle/new", name="admin_cycle_new", methods={"GET"})
     * @return Response
     */
    public function newAction()
    {
        $entity = new Cycle();
        $form = $this->createForm(CycleType::class, $entity);
        return $this->render('Cycle/new.html.twig', [
            'entity' => $entity,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/admin/cycle/{id}/show", name="admin_cycle_show", methods={"GET"})
     * @param EntityManagerInterface $entityManager
     * @param $id
     * @return Response
     */
    public function showAction(EntityManagerInterface $entityManager, $id)
    {
        $entity = $entityManager->getRepository(Cycle::class)->find($id);
        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Cycle entity.');
        }
        $deleteForm = $this->createDeleteForm($id);
        return $this->render('Cycle/show.html.twig', [
            'entity' => $entity,
            'delete_form' => $deleteForm->createView(),
        ]);
    }

    /**
     * @Route("/admin/cycle/{id}/edit", name="admin_cycle_edit", methods={"GET"})
     * @param EntityManagerInterface $entityManager
     * @param $id
     * @return Response
     */
    public function editAction(EntityManagerInterface $entityManager, $id)
    {
        $entity = $entityManager->getRepository(Cycle::class)->find($id);
        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Cycle entity.');
        }
        $editForm = $this->createForm(CycleType::class, $entity);
        $deleteForm = $this->createDeleteForm($id);
        return $this->render('Cycle/edit.html.twig', [
            'entity' => $entity,
            'edit_form' => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ]);
    }

    /**
     * @Route("/admin/cycle/{id}/update", name="admin_cycle_update", methods={"POST", "PUT"})
     * @param EntityManagerInterface $entityManager
     * @param Request $request
     * @param $id
     * @return RedirectResponse|Response
     */
    public function updateAction(EntityManagerInterface $entityManager, Request $request, $id)
    {
        $entity = $entityManager->getRepository(Cycle::class)->find($id);
        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Cycle entity.');
        }
        $deleteForm = $this->createDeleteForm($id);
        $editForm = $this->createForm(CycleType::class, $entity);
        $editForm->handleRequest($request);
        if ($editForm->isValid()) {
            $entityManager->persist($entity);
            $entityManager->flush();
            return $this->redirect($this->generateUrl('admin_cycle_edit', ['id' => $id]));
        }
        return $this->render('Cycle/edit.html.twig', [
            'entity' => $entity,
            'edit_form' => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ]);
    }

    /**
     * @Route("/admin/cycle/{id}/delete", name="admin_cycle_delete", methods={"POST", "DELETE"})
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
            $entity = $entityManager->getRepository(Cycle::class)->find($id);
            if (!$entity) {
                throw $this->createNotFoundException('Unable to find Cycle entity.');
            }
            $entityManager->remove($entity);
            $entityManager->flush();
        }
        return $this->redirect($this->generateUrl('admin_cycle'));
    }

    /**
     * @param $id
     * @return FormInterface
     */
    protected function createDeleteForm($id)
    {
        return $this->createFormBuilder(['id' => $id])
            ->add('id', 'hidden')
            ->getForm()
        ;
    }
}
