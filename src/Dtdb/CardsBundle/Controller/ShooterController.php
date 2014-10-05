<?php

namespace Dtdb\CardsBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use Dtdb\CardsBundle\Entity\Shooter;
use Dtdb\CardsBundle\Form\ShooterType;

/**
 * Shooter controller.
 *
 */
class ShooterController extends Controller
{

    /**
     * Lists all Shooter entities.
     *
     */
    public function indexAction()
    {
        $em = $this->getDoctrine()->getManager();

        $entities = $em->getRepository('DtdbCardsBundle:Shooter')->findAll();

        return $this->render('DtdbCardsBundle:Shooter:index.html.twig', array(
            'entities' => $entities,
        ));
    }
    /**
     * Creates a new Shooter entity.
     *
     */
    public function createAction(Request $request)
    {
        $entity = new Shooter();
        $form = $this->createCreateForm($entity);
        $form->handleRequest($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($entity);
            $em->flush();

            return $this->redirect($this->generateUrl('admin_shooter_show', array('id' => $entity->getId())));
        }

        return $this->render('DtdbCardsBundle:Shooter:new.html.twig', array(
            'entity' => $entity,
            'form'   => $form->createView(),
        ));
    }

    /**
     * Creates a form to create a Shooter entity.
     *
     * @param Shooter $entity The entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createCreateForm(Shooter $entity)
    {
        $form = $this->createForm(new ShooterType(), $entity, array(
            'action' => $this->generateUrl('admin_shooter_create'),
            'method' => 'POST',
        ));

        $form->add('submit', 'submit', array('label' => 'Create'));

        return $form;
    }

    /**
     * Displays a form to create a new Shooter entity.
     *
     */
    public function newAction()
    {
        $entity = new Shooter();
        $form   = $this->createCreateForm($entity);

        return $this->render('DtdbCardsBundle:Shooter:new.html.twig', array(
            'entity' => $entity,
            'form'   => $form->createView(),
        ));
    }

    /**
     * Finds and displays a Shooter entity.
     *
     */
    public function showAction($id)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('DtdbCardsBundle:Shooter')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Shooter entity.');
        }

        $deleteForm = $this->createDeleteForm($id);

        return $this->render('DtdbCardsBundle:Shooter:show.html.twig', array(
            'entity'      => $entity,
            'delete_form' => $deleteForm->createView(),
        ));
    }

    /**
     * Displays a form to edit an existing Shooter entity.
     *
     */
    public function editAction($id)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('DtdbCardsBundle:Shooter')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Shooter entity.');
        }

        $editForm = $this->createEditForm($entity);
        $deleteForm = $this->createDeleteForm($id);

        return $this->render('DtdbCardsBundle:Shooter:edit.html.twig', array(
            'entity'      => $entity,
            'edit_form'   => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ));
    }

    /**
    * Creates a form to edit a Shooter entity.
    *
    * @param Shooter $entity The entity
    *
    * @return \Symfony\Component\Form\Form The form
    */
    private function createEditForm(Shooter $entity)
    {
        $form = $this->createForm(new ShooterType(), $entity, array(
            'action' => $this->generateUrl('admin_shooter_update', array('id' => $entity->getId())),
            'method' => 'PUT',
        ));

        $form->add('submit', 'submit', array('label' => 'Update'));

        return $form;
    }
    /**
     * Edits an existing Shooter entity.
     *
     */
    public function updateAction(Request $request, $id)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('DtdbCardsBundle:Shooter')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Shooter entity.');
        }

        $deleteForm = $this->createDeleteForm($id);
        $editForm = $this->createEditForm($entity);
        $editForm->handleRequest($request);

        if ($editForm->isValid()) {
            $em->flush();

            return $this->redirect($this->generateUrl('admin_shooter_edit', array('id' => $id)));
        }

        return $this->render('DtdbCardsBundle:Shooter:edit.html.twig', array(
            'entity'      => $entity,
            'edit_form'   => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ));
    }
    /**
     * Deletes a Shooter entity.
     *
     */
    public function deleteAction(Request $request, $id)
    {
        $form = $this->createDeleteForm($id);
        $form->handleRequest($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $entity = $em->getRepository('DtdbCardsBundle:Shooter')->find($id);

            if (!$entity) {
                throw $this->createNotFoundException('Unable to find Shooter entity.');
            }

            $em->remove($entity);
            $em->flush();
        }

        return $this->redirect($this->generateUrl('admin_shooter'));
    }

    /**
     * Creates a form to delete a Shooter entity by id.
     *
     * @param mixed $id The entity id
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createDeleteForm($id)
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('admin_shooter_delete', array('id' => $id)))
            ->setMethod('DELETE')
            ->add('submit', 'submit', array('label' => 'Delete'))
            ->getForm()
        ;
    }
}
