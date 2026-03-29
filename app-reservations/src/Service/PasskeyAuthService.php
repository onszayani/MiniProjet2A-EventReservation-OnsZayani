<?php
namespace App\Service;

use App\Entity\User;
use App\Repository\WebauthnCredentialRepository;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Serializer\SerializerInterface;
use Webauthn\Bundle\Service\PublicKeyCredentialCreationOptionsFactory;
use Webauthn\Bundle\Service\PublicKeyCredentialRequestOptionsFactory;
use Webauthn\AuthenticatorAttestationResponseValidator;
use Webauthn\AuthenticatorAssertionResponseValidator;
use Webauthn\PublicKeyCredentialUserEntity;
use Webauthn\PublicKeyCredentialCreationOptions;
use Webauthn\PublicKeyCredentialRequestOptions;
use Webauthn\PublicKeyCredential;

class PasskeyAuthService
{
    public function __construct(
        private PublicKeyCredentialCreationOptionsFactory $creationFactory,
        private PublicKeyCredentialRequestOptionsFactory $requestFactory,
        private AuthenticatorAttestationResponseValidator $attestationValidator,
        private AuthenticatorAssertionResponseValidator $assertionValidator,
        private RequestStack $requestStack,
        private WebauthnCredentialRepository $credRepo,
        private SerializerInterface $serializer
    ) {}

    // INSCRIPTION etape 1 : genere challenge + options pour le navigateur
    public function getRegistrationOptions(User $user): array
    {
        $ue = PublicKeyCredentialUserEntity::create(
            $user->getEmail(),
            (string) $user->getId(),
            $user->getDisplayName()
        );

        $opts = $this->creationFactory->create('default', $ue);
        
        $this->requestStack->getSession()->set("webauthn_reg", $this->serializer->serialize($opts, 'json'));
        
        return json_decode($this->serializer->serialize($opts, 'json'), true);
    }

    // INSCRIPTION etape 2 : verifie la reponse du navigateur et persiste la cle
    public function verifyRegistration(string $json, User $user): void
    {
        $optsJson = $this->requestStack->getSession()->get("webauthn_reg");
        if (!$optsJson) {
            throw new \RuntimeException('Aucune session d\'inscription trouvée.');
        }

        /** @var PublicKeyCredentialCreationOptions $opts  */
        $opts = $this->serializer->deserialize($optsJson, PublicKeyCredentialCreationOptions::class, 'json');

        /** @var PublicKeyCredential $credential */
        $credential = $this->serializer->deserialize($json, PublicKeyCredential::class, 'json');
        
        // We load the credential and assert it
        $credSource = $this->attestationValidator->check(
            $credential->getAuthenticatorResponse(),
            $opts,
            null
        );
        
        $this->credRepo->saveCredential($user, $this->serializer->serialize($credSource, 'json'));
        $this->requestStack->getSession()->remove("webauthn_reg");
    }

    // CONNEXION etape 1 : genere le challenge de connexion
    public function getLoginOptions(): array
    {
        $opts = $this->requestFactory->create('default');
        $this->requestStack->getSession()->set("webauthn_login", $this->serializer->serialize($opts, 'json'));
        return json_decode($this->serializer->serialize($opts, 'json'), true);
    }

    // CONNEXION etape 2 : verifie la signature et retourne l utilisateur
    public function verifyLogin(string $json): User
    {
        $optsJson  = $this->requestStack->getSession()->get("webauthn_login");
        if (!$optsJson) {
            throw new \RuntimeException('Aucune session de connexion trouvée.');
        }

        /** @var PublicKeyCredentialRequestOptions $opts */
        $opts = $this->serializer->deserialize($optsJson, PublicKeyCredentialRequestOptions::class, 'json');

        /** @var PublicKeyCredential $credential */
        $credential = $this->serializer->deserialize($json, PublicKeyCredential::class, 'json');

        // Validation for Assertion needs credential ID, which means Server/Validator will check the user credential.
        $entity = $this->credRepo->findByCredentialId(
            $credential->rawId
        );
        
        if (!$entity) throw new \RuntimeException('Aucune passkey trouvee.');

        $credSourceJson = $entity->getCredentialData();
        $credSource = $this->serializer->deserialize($credSourceJson, \Webauthn\PublicKeyCredentialSource::class, 'json');

        $this->assertionValidator->check(
            $credential->getAuthenticatorResponse(),
             $opts,
             null,
             $credSource->userHandle,
             $credSource
        );

        $entity->touch();
        $this->credRepo->getEntityManager()->flush();

        $this->requestStack->getSession()->remove("webauthn_login");
        return $entity->getUser();
    }
}
