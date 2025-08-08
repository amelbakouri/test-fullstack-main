<?php

declare(strict_types=1);

namespace App\Controller;

use App\Form\CreateClockingType;
use App\Repository\ClockingRepository;
use App\Entity\Clocking;
use App\Entity\ClockingEntry;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/clockings')]
class ClockingCollectionController extends
AbstractController
{

    /**
     * @param \Doctrine\ORM\EntityManagerInterface $entityManager
     * @param \Symfony\Component\HttpFoundation\Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    #[Route('/create', name: 'app_Clocking_create', methods: ['GET', 'POST'])]
    public function createClocking(
        EntityManagerInterface $entityManager,
        Request $request
    ): Response {
        $clocking = new Clocking();

        if ($request->isMethod('GET')) {
            $clocking->addEntry(new ClockingEntry());
        }
        $form = $this->createForm(CreateClockingType::class, $clocking);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // ✅ S'assurer que chaque ClockingEntry connaît son Clocking parent
            foreach ($clocking->getEntries() as $entry) {
                $entry->setClocking($clocking);
            }

            $entityManager->persist($clocking);
            $entityManager->flush();

            return $this->redirectToRoute('app_Clocking_list');
        }

        return $this->render('app/Clocking/create.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * @param \App\Repository\ClockingRepository $clockingRepository
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    #[Route('/', name: 'app_Clocking_list', methods: ['GET'])]
    public function listClockings(ClockingRepository $clockingRepository): Response
    {
        $clockings = $clockingRepository->findAll();

        return $this->render('app/Clocking/list.html.twig', [
            'clockings' => $clockings,
        ]);
    }
}
