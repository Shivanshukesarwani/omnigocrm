<?php

namespace Espo\Modules\OmniGoCRM\Api;

use Espo\Core\Api\Action;
use Espo\Core\Api\Request;
use Espo\Core\Api\Response;
use Espo\Core\Api\ResponseComposer;
use Espo\Core\Exceptions\BadRequest;
use Espo\Core\ORM\EntityManager;
use Espo\Entities\User;

class PostDeviceRegister implements Action
{
    public function __construct(
        private EntityManager $entityManager,
        private User $user,
    ) {}

    public function process(Request $request): Response
    {
        $data = $request->getParsedBody();

        if ($data === null) {
            throw new BadRequest('A JSON payload is required.');
        }

        $platform = $this->value($data, 'platform');
        $pushProvider = $this->value($data, 'pushProvider');
        $pushToken = $this->value($data, 'pushToken');
        $externalDeviceId = $this->value($data, 'externalDeviceId');
        $deviceName = $this->value($data, 'deviceName');
        $appVersion = $this->value($data, 'appVersion');

        if (!in_array($platform, ['Android', 'iOS', 'Web'], true)) {
            throw new BadRequest('Invalid platform.');
        }

        if (!in_array($pushProvider, ['FCM', 'APNs', 'WebPush'], true)) {
            throw new BadRequest('Invalid pushProvider.');
        }

        if ($pushToken === '') {
            throw new BadRequest('pushToken is required.');
        }

        if (mb_strlen($pushToken) > 4096) {
            throw new BadRequest('pushToken is too long.');
        }

        $repo = $this->entityManager->getRDBRepository('OmniGoCRMDevice');

        $device = null;

        if ($externalDeviceId !== '') {
            $device = $repo->where([
                'externalDeviceId' => $externalDeviceId,
                'userId' => $this->user->getId(),
                'deleted' => false,
            ])->findOne();
        }

        if (!$device) {
            $device = $repo->getNew();
        }

        $device->set([
            'name' => $deviceName !== '' ? $deviceName : $platform . ' device',
            'externalDeviceId' => $externalDeviceId !== '' ? $externalDeviceId : null,
            'platform' => $platform,
            'pushProvider' => $pushProvider,
            'pushToken' => $pushToken,
            'appVersion' => $appVersion !== '' ? $appVersion : null,
            'active' => true,
            'lastSeenAt' => gmdate('Y-m-d H:i:s'),
            'userId' => $this->user->getId(),
        ]);

        $this->entityManager->saveEntity($device);

        return ResponseComposer::json([
            'accepted' => true,
            'deviceId' => $device->getId(),
            'platform' => $platform,
            'pushProvider' => $pushProvider,
        ]);
    }

    private function value(\stdClass $data, string $key): string
    {
        return isset($data->{$key}) && is_string($data->{$key})
            ? trim($data->{$key})
            : '';
    }
}
