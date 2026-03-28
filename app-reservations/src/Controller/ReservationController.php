<?php

namespace App\Controller;

use App\Entity\Event;
use App\Entity\Reservation;
use App\Repository\ReservationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ReservationController extends AbstractController
{
    #[Route('/reservations/my', name: 'reservations_my')]
    public function myReservations(ReservationRepository $repository): Response
    {
        $user = $this->getUser();

        if (!$user) {
            return $this->redirectToRoute('auth_login');
        }

        $reservations = $repository->findBy(['user' => $user]);

        return $this->render('reservations/my_res.html.twig', [
            'reservations' => $reservations,
        ]);
    }

    #[Route('/api/reserver/{id}', name: 'api_reserver', methods: ['POST'])]
    public function reserver(Event $event, EntityManagerInterface $em): JsonResponse
    {
        $user = $this->getUser();

        if (!$user) {
            return $this->json(['error' => 'Non authentifié'], 401);
        }

        // Vérifier les places disponibles
        $reservationsCount = $em->getRepository(Reservation::class)
            ->count(['event' => $event, 'status' => Reservation::STATUS_ACCEPTED]);

        if ($reservationsCount >= $event->getSeats()) {
            return $this->json(['error' => 'Plus de places disponibles'], 400);
        }

        $reservation = new Reservation($user, $event);
        $em->persist($reservation);
        $em->flush();

        return $this->json(['success' => true, 'message' => 'Réservation effectuée avec succès']);
    }
}
