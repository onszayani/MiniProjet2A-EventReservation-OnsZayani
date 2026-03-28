<?php

namespace App\DataFixtures;

use App\Entity\Event;
use App\Entity\User;
use App\Entity\Reservation;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class AppFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        // ───────────────── USERS ─────────────────
        $admin = new User();
        $admin->setEmail('admin@issat.tn');
        $admin->setDisplayName('Admin ISSAT');
        $admin->setRoles(['ROLE_ADMIN']);
        $admin->setPassword('password');
        $manager->persist($admin);

        $users = [];
        $usersData = [
            ['ahmed.benali@issat.tn', 'Ahmed Ben Ali'],
            ['sarra.trabelsi@issat.tn', 'Sarra Trabelsi'],
            ['med.khelifi@issat.tn', 'Mohamed Khelifi'],
            ['ines.mansouri@issat.tn', 'Ines Mansouri'],
            ['youssef.sfar@issat.tn', 'Youssef Sfar'],
        ];

        foreach ($usersData as [$email, $name]) {
            $u = new User();
            $u->setEmail($email);
            $u->setDisplayName($name);
            $u->setRoles(['ROLE_USER']);
            $u->setPassword('password');
            $manager->persist($u);
            $users[] = $u;
        }

        $manager->flush();

        // ───────────────── EVENTS ─────────────────
        $eventsData = [
            ['Gala Musique', 'Concert classique', '+15 days', 'Sousse', 200],
            ['Conférence IA', 'Tech & cybersécurité', '+20 days', 'ISSAT', 150],
            ['Tournoi Football', 'Inter-universités', '+25 days', 'Stade', 500],
            ['Théâtre', 'Pièce moderne', '+30 days', 'Théâtre', 80],
            ['Exposition', 'Art numérique', '+10 days', 'Musée', 300],
        ];

        $events = [];

        foreach ($eventsData as [$title, $desc, $date, $location, $seats]) {
            $e = new Event();
            $e->setTitle($title);
            $e->setDescription($desc);
            $e->setDateDeb(new \DateTimeImmutable($date));
            $e->setDateFin(new \DateTimeImmutable($date . ' +2 hours'));
            $e->setLocation($location);
            $e->setSeats($seats);
            $e->setImage('default.jpg');

            $manager->persist($e);
            $events[] = $e;
        }


        $manager->flush();

        // ───────────────── RESERVATIONS ─────────────────
        $statuses = [
            Reservation::STATUS_ACCEPTED,
            Reservation::STATUS_PENDING,
            Reservation::STATUS_REJECTED
        ];

        foreach ($users as $user) {
            foreach ($events as $event) {
                $r = new Reservation($user, $event);
                $r->setStatus($statuses[array_rand($statuses)]);
                $manager->persist($r);
            }
        }

        $manager->flush();
    }
}
