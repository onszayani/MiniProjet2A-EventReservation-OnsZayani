<?php

namespace App\Controller;

use App\Entity\Event;
use App\Entity\Reservation;
use App\Repository\ReservationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
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

    // NOUVELLE ROUTE POUR LE FORMULAIRE
    #[Route('/reservation/create', name: 'reservation_create', methods: ['POST'])]
    public function createReservation(Request $request, EntityManagerInterface $em): Response
    {
        $eventId = $request->request->get('event_id');
        $event = $em->getRepository(Event::class)->find($eventId);

        if (!$event) {
            $this->addFlash('reservation_error', 'Événement introuvable.');
            return $this->redirectToRoute('app_events');
        }

        // Récupérer l'utilisateur connecté
        $user = $this->getUser();

        if (!$user) {
            $this->addFlash('reservation_error', 'Veuillez vous connecter pour réserver.');
            return $this->redirectToRoute('auth_login');
        }

        // Vérifier les places disponibles
        $reservationsCount = $em->getRepository(Reservation::class)
            ->count(['event' => $event, 'status' => Reservation::STATUS_ACCEPTED]);

        if ($reservationsCount >= $event->getSeats()) {
            $this->addFlash('reservation_error', 'Désolé, il n\'y a plus de places disponibles.');
            return $this->redirectToRoute('event_details', ['id' => $event->getId()]);
        }

        // Créer la réservation
        $reservation = new Reservation($user, $event);

        // Optionnel: Si vous voulez stocker le nom, email, téléphone dans la réservation
        // Vous devrez ajouter ces champs à l'entité Reservation
        // $reservation->setName($request->request->get('name'));
        // $reservation->setEmail($request->request->get('email'));
        // $reservation->setPhone($request->request->get('phone'));

        $em->persist($reservation);
        $em->flush();

        $this->addFlash('reservation_success', 'Votre réservation a été confirmée avec succès ! Vous recevrez un email de confirmation.');

        return $this->redirectToRoute('event_details', ['id' => $event->getId()]);
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
