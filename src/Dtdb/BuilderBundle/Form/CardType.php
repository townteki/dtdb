<?php

namespace Dtdb\BuilderBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class CardType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('code')
            ->add('number')
            ->add('quantity')
            ->add('title')
            ->add('keywords')
            ->add('text', 'textarea', array('required' => false))
            ->add('flavor', 'textarea', array('required' => false))
            ->add('illustrator')
            ->add('cost')
            ->add('rank')
            ->add('upkeep')
            ->add('production')
            ->add('bullets')
            ->add('influence')
            ->add('control')
            ->add('wealth')
            ->add('pack', 'entity', array('class' => 'DtdbBuilderBundle:Pack', 'property' => 'name'))
            ->add('type', 'entity', array('class' => 'DtdbBuilderBundle:Type', 'property' => 'name'))
            ->add('shooter', 'entity', array('class' => 'DtdbBuilderBundle:Shooter', 'property' => 'name', 'required' => false))
            ->add('gang', 'entity', array('class' => 'DtdbBuilderBundle:Gang', 'property' => 'name', 'required' => false))
        ;
    }
    
    /**
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'Dtdb\BuilderBundle\Entity\Card'
        ));
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'dtdb_BuilderBundle_card';
    }
}
