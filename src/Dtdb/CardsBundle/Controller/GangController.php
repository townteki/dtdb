<?php

namespace Dtdb\CardsBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use Dtdb\CardsBundle\Entity\Gang;
use Dtdb\CardsBundle\Form\GangType;

/**
 * Gang controller.
 *
 */
class GangController extends Controller
{

    /**
     * Lists all Gang entities.
     *
     */
    public function indexAction()
    {
        $em = $this->getDoctrine()->getManager();

        $entities = $em->getRepository('DtdbCardsBundle:Gang')->findAll();

        return $this->render('DtdbCardsBundle:Gang:index.html.twig', array(
            'entities' => $entities,
        ));
    }
    /**
     * Creates a new Gang entity.
     *
     */
    public function createAction(Request $request)
    {
        $entity = new Gang();
        $form = $this->createCreateForm($entity);
        $form->handleRequest($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($entity);
            $em->flush();

            return $this->redirect($this->generateUrl('admin_gang_show', array('id' => $entity->getId())));
        }

        return $this->render('DtdbCardsBundle:Gang:new.html.twig', array(
            'entity' => $entity,
            'form'   => $form->createView(),
        ));
    }

    /**
     * Creates a form to create a Gang entity.
     *
     * @param Gang $entity The entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createCreateForm(Gang $entity)
    {
        $form = $this->createForm(new GangType(), $entity, array(
            'action' => $this->generateUrl('admin_gang_create'),
            'method' => 'POST',
        ));

        $form->add('submit', 'submit', array('label' => 'Create'));

        return $form;
    }

    /**
     * Displays a form to create a new Gang entity.
     *
     */
    public function newAction()
    {
        $entity = new Gang();
        $form   = $this->createCreateForm($entity);

        return $this->render('DtdbCardsBundle:Gang:new.html.twig', array(
            'entity' => $entity,
            'form'   => $form->createView(),
        ));
    }

    /**
     * Finds and displays a Gang entity.
     *
     */
    public function showAction($id)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('DtdbCardsBundle:Gang')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Gang entity.');
        }

        $deleteForm = $this->createDeleteForm($id);

        return $this->render('DtdbCardsBundle:Gang:show.html.twig', array(
            'entity'      => $entity,
            'delete_form' => $deleteForm->createView(),
        ));
    }

    /**
     * Displays a form to edit an existing Gang entity.
     *
     */
    public function editAction($id)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('DtdbCardsBundle:Gang')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Gang entity.');
        }

        $editForm = $this->createEditForm($entity);
        $deleteForm = $this->createDeleteForm($id);

        return $this->render('DtdbCardsBundle:Gang:edit.html.twig', array(
            'entity'      => $entity,
            'edit_form'   => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ));
    }

    /**
    * Creates a form to edit a Gang entity.
    *
    * @param Gang $entity The entity
    *
    * @return \Symfony\Component\Form\Form The form
    */
    private function createEditForm(Gang $entity)
    {
        $form = $this->createForm(new GangType(), $entity, array(
            'action' => $this->generateUrl('admin_gang_update', array('id' => $entity->getId())),
            'method' => 'PUT',
        ));

        $form->add('submit', 'submit', array('label' => 'Update'));

        return $form;
    }
    /**
     * Edits an existing Gang entity.
     *
     */
    public function updateAction(Request $request, $id)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('DtdbCardsBundle:Gang')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Gang entity.');
        }

        $deleteForm = $this->createDeleteForm($id);
        $editForm = $this->createEditForm($entity);
        $editForm->handleRequest($request);

        if ($editForm->isValid()) {
            $em->flush();

            return $this->redirect($this->generateUrl('admin_gang_edit', array('id' => $id)));
        }

        return $this->render('DtdbCardsBundle:Gang:edit.html.twig', array(
            'entity'      => $entity,
            'edit_form'   => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ));
    }
    /**
     * Deletes a Gang entity.
     *
     */
    public function deleteAction(Request $request, $id)
    {
        $form = $this->createDeleteForm($id);
        $form->handleRequest($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $entity = $em->getRepository('DtdbCardsBundle:Gang')->find($id);

            if (!$entity) {
                throw $this->createNotFoundException('Unable to find Gang entity.');
            }

            $em->remove($entity);
            $em->flush();
        }

        return $this->redirect($this->generateUrl('admin_gang'));
    }

    /**
     * Creates a form to delete a Gang entity by id.
     *
     * @param mixed $id The entity id
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createDeleteForm($id)
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('admin_gang_delete', array('id' => $id)))
            ->setMethod('DELETE')
            ->add('submit', 'submit', array('label' => 'Delete'))
            ->getForm()
        ;
    }
}
