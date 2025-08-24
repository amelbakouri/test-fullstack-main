<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Clocking;
use App\Entity\ClockingEntry;
use App\Form\MultiClockingType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('IS_AUTHENTICATED_FULLY')]
class ClockingMultiController extends AbstractController
{

    #[Route('/clockings/multi', name: 'app_clocking_multi', methods: ['GET', 'POST'])]
    public function multiClocking(Request $request, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(MultiClockingType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $project = $form->get('project')->getData();
            $date    = $form->get('date')->getData();
            $rows    = $form->get('entries')->getData();

            foreach ($form->get('entries') as $rowForm) {
                $user     = $rowForm->get('user')->getData();
                $duration = (int)$rowForm->get('duration')->getData();

                if (!$user || $duration <= 0) {
                    continue;
                }

                $clocking = $em->getRepository(Clocking::class)->findOneBy([
                    'clockingUser' => $user,
                    'date'         => $date,
                ]);

                if (!$clocking) {
                    $clocking = new Clocking();
                    $clocking->setClockingUser($user);
                    $clocking->setDate($date);
                    $em->persist($clocking);
                }

                $entry = new ClockingEntry();
                $entry->setProject($project);
                $entry->setDuration($duration);
                $entry->setClocking($clocking);

                $clocking->addEntry($entry);
                $em->persist($entry);
            }


            $em->flush();


            return $this->redirectToRoute('app_Clocking_list');
        }

        return $this->render('app/Clocking/multi_create.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
