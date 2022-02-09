<?php

namespace App\Form\Type;

use App\Entity\Card;
use App\Entity\Gang;
use App\Entity\Pack;
use App\Entity\Shooter;
use App\Entity\Type;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CardType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('code')
            ->add('number')
            ->add('quantity')
            ->add('title')
            ->add('keywords')
            ->add('text', TextareaType::class, array('required' => false))
            ->add('flavor', TextareaType::class, array('required' => false))
            ->add('illustrator')
            ->add('cost')
            ->add('rank')
            ->add('upkeep')
            ->add('production')
            ->add('bullets')
            ->add('influence')
            ->add('control')
            ->add('wealth')
            ->add('pack', EntityType::class, array('class' => Pack::class, 'choice_label' => 'name'))
            ->add('type', EntityType::class, array('class' => Type::class, 'choice_label' => 'name'))
            ->add(
                'shooter',
                EntityType::class,
                array('class' => Shooter::class, 'choice_label' => 'name', 'required' => false)
            )
            ->add(
                'gang',
                EntityType::class,
                array('class' => Gang::class, 'choice_label' => 'name', 'required' => false)
            );
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => Card::class,
        ));
    }
}
