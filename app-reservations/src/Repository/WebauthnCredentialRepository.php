<?php

namespace App\Repository;

use App\Entity\WebauthnCredential;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<WebauthnCredential>
 */
class WebauthnCredentialRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, WebauthnCredential::class);
    }


    public function getDescriptorsForUser(User $user): array
    {
        // ... pas utilisé ici
        return [];
    }

    public function saveCredential(
        User $user, string $credentialSourceJson, string $name='Ma passkey'
    ): WebauthnCredential {
        $c = new WebauthnCredential($user, $name);
        $c->setCredentialData($credentialSourceJson);
        $this->getEntityManager()->persist($c);
        $this->getEntityManager()->flush();
        return $c;
    }

    public function findByCredentialId(string $credentialId): ?WebauthnCredential
    {
        foreach ($this->findAll() as $cred) {
            $data = json_decode($cred->getCredentialData(), true);
            if (isset($data['publicKeyCredentialId']) && $data['publicKeyCredentialId'] === $credentialId) {
                return $cred;
            }
        }
        return null;
    }

}
