<?php

namespace App\Form;

use App\Entity\Project;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints as Assert;

class MultiClockingType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('date', DateType::class, [
                'label' => 'Date',
                'widget' => 'single_text',
                'mapped' => false,
                'constraints' => [new Assert\NotBlank()],
            ])
            ->add('project', EntityType::class, [
                'class' => Project::class,
                'choice_label' => 'name',
                'label' => 'Projet',
                'placeholder' => 'Choisir un chantier',
                'mapped' => false,
                'constraints' => [new Assert\NotBlank()],
            ])
            ->add('entries', CollectionType::class, [
                'entry_type' => \App\Form\UserDurationType::class,
                'entry_options' => ['label' => false],
                'allow_add' => true,
                'by_reference' => false,
                'mapped' => false,
                'prototype' => true,
                'prototype_name' => '__name__',
            ]);
    }
}
