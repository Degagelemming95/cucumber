<?php
declare(strict_types=1);

namespace adeynes\cucumber\command;

use adeynes\cucumber\Cucumber;
use adeynes\cucumber\utils\CucumberException;
use adeynes\cucumber\utils\CucumberPlayer;
use adeynes\cucumber\utils\Queries;
use adeynes\parsecmd\command\blueprint\CommandBlueprint;
use adeynes\parsecmd\command\CommandParser;
use adeynes\parsecmd\command\ParsedCommand;
use pocketmine\command\CommandSender;

class IpbanCommand extends CucumberCommand
{

    public function __construct(Cucumber $plugin, CommandBlueprint $blueprint)
    {
        parent::__construct(
            $plugin,
            $blueprint,
            'ipban',
            'cucumber.command.ipban',
            'Ban an IP',
            '/ipban <-p <player>|-ip <ip>> [reason]'
        );
    }

    public function _execute(CommandSender $sender, ParsedCommand $command): bool
    {
        [$target, $reason] = $command->get(['target', 'reason']);
        if ($reason === '') $reason = null;
        $duration = $command->getFlag('duration');
        $expiration = $duration ? CommandParser::parseDuration($duration) : null;

        $ip_ban = function (string $ip) use ($sender, $reason, $expiration) {
            try {
                $ban_data = $this->getPlugin()->getPunishmentManager()
                    ->ipBan($ip, $reason, $expiration, $sender->getName())
                    ->getDataFormatted();
                $ban_data = $ban_data + ['ip' => $ip];

                foreach ($this->getPlugin()->getServer()->getOnlinePlayers() as $player) {
                    if ($player->getAddress() === $ip) {
                        $player->kick(
                            $this->getPlugin()->formatMessageFromConfig('moderation.ban.message', $ban_data),
                            false // don't say Kicked by admin
                        );
                    }
                }

                $this->getPlugin()->formatAndSend($sender, 'success.ipban', $ban_data);
                return true;
            } catch (CucumberException $exception) {
                $sender->sendMessage($exception->getMessage());
                return false;
            }
        };

        // TODO: refactor this logic
        if (!is_null($ip_flag = $command->getFlag('ip')) xor
            !is_null($player_flag = $command->getFlag('player'))) {

            if (!is_null($ip_flag)) {
                $ip_ban($target);
            } elseif (!is_null($player_flag)) {
                if ($player = CucumberPlayer::getOnlinePlayer($target)) {
                    $ip_ban($player->getAddress());
                } else {
                    $this->doIfTargetExists(
                        function (array $rows) use ($ip_ban) {
                            $ip_ban($rows[0]['ip']);
                        },
                        $sender,
                        $target
                    );
                }
            }
        } else {
            $ip_matches = [];

            preg_match('/^\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}$/', $target, $ip_matches);

            if ($ip_matches === []) {
                if ($player = CucumberPlayer::getOnlinePlayer($target)) {
                    $ip_ban($player->getAddress());
                } else {
                    $this->doIfTargetExists(
                        function (array $rows) use ($ip_ban) {
                            $ip_ban($rows[0]['ip']);
                        },
                        $sender,
                        $target
                    );
                }
            } else {
                $ip = $ip_matches[0];
                $this->getPlugin()->getConnector()->executeSelect(
                    Queries::CUCUMBER_GET_PLAYER_BY_NAME,
                    ['name' => $ip],
                    function (array $rows) use ($sender, $ip, $ip_ban) {
                        if (count($rows) !== 0) {
                            $this->getPlugin()->formatAndSend($sender, 'error.player-ip-ambiguity', ['ip' => $ip]);
                            return;
                        }

                        $ip_ban($ip);
                    }
                );
            }
        }

        /*if ($target_name) {
            if ($target = CucumberPlayer::getOnlinePlayer($target_name)) {
                $ip_ban($target->getAddress());
            } else {
                $this->doIfTargetExists(
                    function (array $rows) use ($ip_ban) {
                        $ip_ban($rows[0]['ip']);
                    },
                    $sender,
                    $target_name
                );
            }
            // don't return in case ip flag is set
        }

        if ($ip) {
            $ip_ban($ip);
        }

        if (!$target_name && !$ip) {
            $sender->sendMessage(
                MessageFactory::colorize("&cAt least one of flag &b-p &cand flag &b-ip&c must be set!")
            );
        }*/

        return true;
    }

}