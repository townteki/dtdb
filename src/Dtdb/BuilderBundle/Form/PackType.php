<?php

namespace Dtdb\BuilderBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class PackType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('code')
            ->add('name')
            ->add('released')
            ->add('size')
            ->add('number')
            ->add('cycle', 'entity', array('class' => 'DtdbBuilderBundle:Cycle', 'property' => 'name'))
        ;
    }
    
    /**
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'Dtdb\BuilderBundle\Entity\Pack'
        ));
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'dtdb_BuilderBundle_pack';
    }
}
