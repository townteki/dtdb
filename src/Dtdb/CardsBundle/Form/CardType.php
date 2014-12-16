<?php

namespace Dtdb\CardsBundle\Form;

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
            ->add('pack', 'entity', array('class' => 'DtdbCardsBundle:Pack', 'property' => 'name'))
            ->add('type', 'entity', array('class' => 'DtdbCardsBundle:Type', 'property' => 'name'))
            ->add('shooter', 'entity', array('class' => 'DtdbCardsBundle:Shooter', 'property' => 'name', 'required' => false))
            ->add('gang', 'entity', array('class' => 'DtdbCardsBundle:Gang', 'property' => 'name', 'required' => false))
        ;
    }
    
    /**
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'Dtdb\CardsBundle\Entity\Card'
        ));
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'dtdb_cardsbundle_card';
    }
}
