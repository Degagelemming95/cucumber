<?php
declare(strict_types=1);

namespace cucumber\task;

class PunishmentSaveTask extends CTask
{

    public function onRun(int $tick): void
    {
        $this->getPlugin()->log('Saving punishments...');
        $this->getPlugin()->getPunishmentManager()->save();
    }

}