<?php

namespace App\Controller;

use App\Entity\User;
use App\Service\PasskeyAuthService;

use Doctrine\ORM\EntityManagerInterface;

use Gesdinet\JWTRefreshTokenBundle\Model\RefreshTokenManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/auth')]
class AuthController extends AbstractController
{
    public function __construct(
        private JWTTokenManagerInterface $jwt,
        private RefreshTokenManagerInterface $refresh
    ) {}

    /** ÉTAPE 1 · INSCRIPTION */
    #[Route('/register/options', methods: ['POST'])]
    public function registerOptions(
        Request $request,
        EntityManagerInterface $em,
        PasskeyAuthService $svc
    ): JsonResponse {

        $data = json_decode($request->getContent(), true);
        $email = trim($data['email'] ?? '');
        $display = trim($data['displayName'] ?? $email);

        if (!$email)
            return $this->json(['error' => 'Email requis'], 400);

        $user = $em->getRepository(User::class)->findOneBy(['email' => $email]);

        if (!$user) {
            $user = (new User())
                ->setEmail($email)
                ->setDisplayName($display)
                ->setPassword(bin2hex(random_bytes(32))); // Dummy password for Passkey-only users
            $em->persist($user);
            $em->flush();
        }

        return $this->json($svc->getRegistrationOptions($user));
    }

    /** ÉTAPE 2 · INSCRIPTION */
    #[Route('/register/verify', methods: ['POST'])]
    public function registerVerify(
        Request $request,
        EntityManagerInterface $em,
        PasskeyAuthService $svc
    ): JsonResponse {

        $data = json_decode($request->getContent(), true);

        $email = trim($data['email'] ?? '');
        $cred  = $data['credential'] ?? null;

        if (!$email || !$cred)
            return $this->json(['error' => 'Données invalides'], 400);

        $user = $em->getRepository(User::class)->findOneBy(['email' => $email]);

        $svc->verifyRegistration(json_encode($cred), $user);

        $jwt = $this->jwt->create($user);
        $refresh = $this->refresh->createForUser($user)->getRefreshToken();

        return $this->json([
            'success' => true,
            'token' => $jwt,
            'refresh_token' => $refresh,
            'user' => [
                'id' => $user->getId(),
                'email' => $user->getEmail(),
                'displayName' => $user->getDisplayName(),
                'roles' => $user->getRoles()
            ]
        ]);
    }

    /** ÉTAPE 1 · CONNEXION */
    #[Route('/login/options', methods: ['POST'])]
    public function loginOptions(
        Request $req,
        EntityManagerInterface $em,
        PasskeyAuthService $svc
    ): JsonResponse {

        $data = json_decode($req->getContent(), true);
        $email = trim($data['email'] ?? '');

        if (!$email) return $this->json(['error' => 'Email requis'], 400);

        $user = $em->getRepository(User::class)->findOneBy(['email' => $email]);

        return $this->json($svc->getLoginOptions($user));
    }

    /** ÉTAPE 2 · CONNEXION */
    #[Route('/login/verify', methods: ['POST'])]
    public function loginVerify(
        Request $request,
        PasskeyAuthService $svc
    ): JsonResponse {

        $data = json_decode($request->getContent(), true);
        $cred = $data['credential'] ?? null;

        if (!$cred)
            return $this->json(['error' => 'Credential manquant'], 400);

        $user = $svc->verifyLogin(json_encode($cred));

        $jwt = $this->jwt->create($user);
        $refresh = $this->refresh->createForUser($user)->getRefreshToken();

        return $this->json([
            'success' => true,
            'token' => $jwt,
            'refresh_token' => $refresh,
            'user' => [
                'id' => $user->getId(),
                'email' => $user->getEmail(),
                'displayName' => $user->getDisplayName(),
                'roles' => $user->getRoles()
            ]
        ]);
    }
}
