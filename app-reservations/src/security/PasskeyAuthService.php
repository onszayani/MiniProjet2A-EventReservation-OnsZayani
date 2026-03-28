<?php
namespace App\Service;
use App\Entity\User;
use App\Repository\WebauthnCredentialRepository;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Webauthn\{PublicKeyCredentialUserEntity, Server};

class PasskeyAuthService
{
    public function __construct(
        private Server $webauthnServer,
        private SessionInterface $session,
        private WebauthnCredentialRepository $credRepo
    ) {}

    // INSCRIPTION etape 1 : genere challenge + options pour le navigateur
    public function getRegistrationOptions(User $user): array
    {
        $ue = new PublicKeyCredentialUserEntity(
            $user->getEmail(), $user->getId()->toBinary(), $user->getDisplayName()
        );
        $opts = $this->webauthnServer->generatePublicKeyCredentialCreationOptions(
            $ue, authenticatorSelection:null, excludeCredentials:[]
        );
        $this->session->set("webauthn_reg", $opts);
        return $opts->jsonSerialize();
    }

    // INSCRIPTION etape 2 : verifie la reponse du navigateur et persiste la cle
    public function verifyRegistration(string $json, User $user): void
    {
        $opts = $this->session->get("webauthn_reg");
        $ue   = new PublicKeyCredentialUserEntity(
            $user->getEmail(), $user->getId()->toBinary(), $user->getDisplayName()
        );
        $cred = $this->webauthnServer->loadAndCheckAttestationResponse($json,$opts,$ue);
        $this->credRepo->saveCredential($user, $cred);
        $this->session->remove("webauthn_reg");
    }

    // CONNEXION etape 1 : genere le challenge de connexion
    public function getLoginOptions(): array
    {
        $opts = $this->webauthnServer->generatePublicKeyCredentialRequestOptions();
        $this->session->set("webauthn_login", $opts);
        return $opts->jsonSerialize();
    }

    // CONNEXION etape 2 : verifie la signature et retourne l utilisateur
    public function verifyLogin(string $json): User
    {
        $opts  = $this->session->get("webauthn_login");
        $cred  = $this->webauthnServer->loadAndCheckAssertionResponse($json,$opts);
        $entity = $this->credRepo->findByCredentialId(
            $cred->getPublicKeyCredentialId()
        );
        if (!$entity) throw new \RuntimeException('Aucune passkey trouvee.');
        $entity->touch();
        $this->session->remove("webauthn_login");
        return $entity->getUser();
    }
}
