<?php

namespace Dtdb\BuilderBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use Dtdb\BuilderBundle\Entity\Suit;
use Dtdb\BuilderBundle\Form\SuitType;

/**
 * Suit controller.
 *
 */
class SuitController extends Controller
{

    /**
     * Lists all Suit entities.
     *
     */
    public function indexAction()
    {
        $em = $this->getDoctrine()->getManager();

        $entities = $em->getRepository('DtdbBuilderBundle:Suit')->findAll();

        return $this->render('DtdbBuilderBundle:Suit:index.html.twig', array(
            'entities' => $entities,
        ));
    }
    /**
     * Creates a new Suit entity.
     *
     */
    public function createAction(Request $request)
    {
        $entity = new Suit();
        $form = $this->createCreateForm($entity);
        $form->handleRequest($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($entity);
            $em->flush();

            return $this->redirect($this->generateUrl('admin_suit_show', array('id' => $entity->getId())));
        }

        return $this->render('DtdbBuilderBundle:Suit:new.html.twig', array(
            'entity' => $entity,
            'form'   => $form->createView(),
        ));
    }

    /**
     * Creates a form to create a Suit entity.
     *
     * @param Suit $entity The entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createCreateForm(Suit $entity)
    {
        $form = $this->createForm(new SuitType(), $entity, array(
            'action' => $this->generateUrl('admin_suit_create'),
            'method' => 'POST',
        ));

        $form->add('submit', 'submit', array('label' => 'Create'));

        return $form;
    }

    /**
     * Displays a form to create a new Suit entity.
     *
     */
    public function newAction()
    {
        $entity = new Suit();
        $form   = $this->createCreateForm($entity);

        return $this->render('DtdbBuilderBundle:Suit:new.html.twig', array(
            'entity' => $entity,
            'form'   => $form->createView(),
        ));
    }

    /**
     * Finds and displays a Suit entity.
     *
     */
    public function showAction($id)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('DtdbBuilderBundle:Suit')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Suit entity.');
        }

        $deleteForm = $this->createDeleteForm($id);

        return $this->render('DtdbBuilderBundle:Suit:show.html.twig', array(
            'entity'      => $entity,
            'delete_form' => $deleteForm->createView(),
        ));
    }

    /**
     * Displays a form to edit an existing Suit entity.
     *
     */
    public function editAction($id)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('DtdbBuilderBundle:Suit')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Suit entity.');
        }

        $editForm = $this->createEditForm($entity);
        $deleteForm = $this->createDeleteForm($id);

        return $this->render('DtdbBuilderBundle:Suit:edit.html.twig', array(
            'entity'      => $entity,
            'edit_form'   => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ));
    }

    /**
    * Creates a form to edit a Suit entity.
    *
    * @param Suit $entity The entity
    *
    * @return \Symfony\Component\Form\Form The form
    */
    private function createEditForm(Suit $entity)
    {
        $form = $this->createForm(new SuitType(), $entity, array(
            'action' => $this->generateUrl('admin_suit_update', array('id' => $entity->getId())),
            'method' => 'PUT',
        ));

        $form->add('submit', 'submit', array('label' => 'Update'));

        return $form;
    }
    /**
     * Edits an existing Suit entity.
     *
     */
    public function updateAction(Request $request, $id)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('DtdbBuilderBundle:Suit')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Suit entity.');
        }

        $deleteForm = $this->createDeleteForm($id);
        $editForm = $this->createEditForm($entity);
        $editForm->handleRequest($request);

        if ($editForm->isValid()) {
            $em->flush();

            return $this->redirect($this->generateUrl('admin_suit_edit', array('id' => $id)));
        }

        return $this->render('DtdbBuilderBundle:Suit:edit.html.twig', array(
            'entity'      => $entity,
            'edit_form'   => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ));
    }
    /**
     * Deletes a Suit entity.
     *
     */
    public function deleteAction(Request $request, $id)
    {
        $form = $this->createDeleteForm($id);
        $form->handleRequest($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $entity = $em->getRepository('DtdbBuilderBundle:Suit')->find($id);

            if (!$entity) {
                throw $this->createNotFoundException('Unable to find Suit entity.');
            }

            $em->remove($entity);
            $em->flush();
        }

        return $this->redirect($this->generateUrl('admin_suit'));
    }

    /**
     * Creates a form to delete a Suit entity by id.
     *
     * @param mixed $id The entity id
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createDeleteForm($id)
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('admin_suit_delete', array('id' => $id)))
            ->setMethod('DELETE')
            ->add('submit', 'submit', array('label' => 'Delete'))
            ->getForm()
        ;
    }
}
