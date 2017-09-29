<?php

namespace Dtdb\BuilderBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class TypeType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('name')
            ->add('suit', 'entity', array('class' => 'DtdbBuilderBundle:Suit', 'property' => 'name', 'required' => false))
        ;
    }
    
    /**
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'Dtdb\BuilderBundle\Entity\Type'
        ));
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'dtdb_BuilderBundle_type';
    }
}
