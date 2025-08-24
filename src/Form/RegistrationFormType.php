<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\IsTrue;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class RegistrationFormType extends AbstractType
{

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('firstName', TextType::class, [
                'constraints' => [
                    new NotBlank(['message' => 'Veuillez saisir votre prénom']),
                    new Length(['max' => 255]),
                ],
            ])
            ->add('lastName', TextType::class, [
                'constraints' => [
                    new NotBlank(['message' => 'Veuillez saisir votre nom']),
                    new Length(['max' => 255]),
                ],
            ])
            ->add('matricule', TextType::class, [
                'constraints' => [
                    new NotBlank(['message' => 'Veuillez saisir votre matricule']),
                    new Length(['max' => 255]),
                ],
            ])
            ->add('role', ChoiceType::class, [
                'mapped'   => false,
                'label'    => 'Rôle',
                'choices'  => [
                    'Collaborateur'  => User::ROLE_USER,
                    'Chef de projet' => User::ROLE_PM,
                ],
                'placeholder' => 'Choisissez un rôle',
            ])


            ->add('agreeTerms', CheckboxType::class, [
                'mapped' => false,
                'constraints' => [new IsTrue(['message' => 'You should agree to our terms.'])],
            ])
            ->add('plainPassword', PasswordType::class, [
                'mapped' => false,
                'attr' => ['autocomplete' => 'new-password'],
                'constraints' => [
                    new NotBlank(['message' => 'Please enter a password']),
                    new Length(['min' => 6, 'max' => 4096]),
                ],
            ]);
    }


    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
