<?php
declare(strict_types=1);

namespace adeynes\cucumber\command;

use adeynes\cucumber\Cucumber;
use adeynes\cucumber\utils\CucumberPlayer;
use adeynes\cucumber\utils\MessageFactory;
use adeynes\parsecmd\command\blueprint\CommandBlueprint;
use adeynes\parsecmd\command\ParsedCommand;
use pocketmine\command\CommandSender;

class WarnCommand extends CucumberCommand
{

    public function __construct(Cucumber $plugin, CommandBlueprint $blueprint)
    {
        parent::__construct(
            $plugin,
            $blueprint,
            'warn',
            'cucumber.command.warn',
            'Send a raw message to a player',
            '/warn <player> <reason>'
        );
    }

    public function _execute(CommandSender $sender, ParsedCommand $command): bool
    {
        [$target_name, $message] = $command->get(['player', 'message']);
        $message = MessageFactory::colorize($message);

        if (!$target = CucumberPlayer::getOnlinePlayer($target_name)) {
            $this->getPlugin()->formatAndSend($sender, 'error.player-offline', ['player' => $target_name]);
            return false;
        }

        if (is_null($command->getFlag('nomessage'))) {
            $target->sendMessage($message);
        }

        $this->getPlugin()->formatAndSend(
            $sender,
            'success.warn',
            ['player' => $target_name, 'message' => $message]
        );
        return true;
    }

}
