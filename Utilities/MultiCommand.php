<?php
namespace amb\libtest\Utilities;

class MultiCommand
{
    private $commands = array();

    public function addCommand($cmd, &$response = null)
    {
        $this->commands[] = array(
            'cmd' => $cmd,
            'response' => $response
        );
    }

    public function getCommands()
    {
        return $this->commands;
    }

    public function countCommands()
    {
        return count($this->commands);
    }

    public function execute()
    {
        foreach ($this->commands as &$command) {
            $command['proc'] = popen($command['cmd'], "r");
        }

        foreach ($this->commands as &$command) {
            $response = '';
            while (!feof($command['proc'])) {
                $response .= fgets($command['proc']);
            }
            $command['response'] = $response;
            pclose($command['proc']);
            unset($command['proc']);
        }
    }
}
