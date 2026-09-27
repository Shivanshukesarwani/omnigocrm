<?php

namespace tests\unit\Espo\Modules\OmniGoCRM;

use PHPUnit\Framework\TestCase;

class WorkspaceSecurityMetadataTest extends TestCase
{
    public function testWorkspaceControlPlaneHooksAreRegistered(): void
    {
        $root = dirname(__DIR__, 4);

        $workspace = json_decode(
            file_get_contents($root . '/custom/Espo/Modules/OmniGoCRM/Resources/metadata/recordDefs/Workspace.json'),
            true,
            512,
            JSON_THROW_ON_ERROR
        );

        $member = json_decode(
            file_get_contents($root . '/custom/Espo/Modules/OmniGoCRM/Resources/metadata/recordDefs/WorkspaceMember.json'),
            true,
            512,
            JSON_THROW_ON_ERROR
        );

        self::assertContains(
            'Espo\\Modules\\OmniGoCRM\\Classes\\Record\\Workspace\\BeforeSave',
            $workspace['beforeSaveHookClassNameList'] ?? []
        );
        self::assertContains(
            'Espo\\Modules\\OmniGoCRM\\Classes\\Record\\Workspace\\BeforeRead',
            $workspace['beforeReadHookClassNameList'] ?? []
        );
        self::assertContains(
            'Espo\\Modules\\OmniGoCRM\\Classes\\Record\\Workspace\\BeforeDelete',
            $workspace['beforeDeleteHookClassNameList'] ?? []
        );

        self::assertContains(
            'Espo\\Modules\\OmniGoCRM\\Classes\\Record\\WorkspaceMember\\BeforeSave',
            $member['beforeSaveHookClassNameList'] ?? []
        );
        self::assertContains(
            'Espo\\Modules\\OmniGoCRM\\Classes\\Record\\WorkspaceMember\\BeforeRead',
            $member['beforeReadHookClassNameList'] ?? []
        );
    }
}
