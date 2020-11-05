<?php

namespace App\Commands\Github;

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;
use LaravelZero\Framework\Commands\Command;

class ValidateLabelPrCommand extends Command
{
    /**
     * The signature of the command.
     *
     * @var string
     */
    protected $signature = 'github:pr:validate-label {org} {project} {pr} {labels}';

    /**
     * The description of the command.
     *
     * @var string
     */
    protected $description = 'Check current PR and valdidate if label exits';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->title('GitHub label validation');

        $client = new Client([
            'base_uri' => 'https://api.github.com'
        ]);

        $headers = [
            'Authorization' => 'token ' . env('GH_TOKEN'),
            'Accept' => 'application/json'
        ];

        $organization = $this->argument('org');
        $project = $this->argument('project');
        $pr = $this->argument('pr');
        $labels = explode(',', $this->argument('labels'));

        $this->table([], [
            ['Organization', $organization],
            ['Project', $project],
            ['Pull Request', $pr],
            ['Labels', $this->argument('labels')],
        ]);

        $uri = sprintf('/repos/%s/%s/pulls/%s', $organization, $project, $pr);

        $request = new Request('GET', $uri, $headers);
        $response = $client->sendRequest($request);

        if ($response->getStatusCode() == 200) {

            $string = $response->getBody();
            $json = json_decode($string, true);

            $foundLabels = 0;
            $requiredLabels = count($labels);

            $this->info('Required labels ' . $requiredLabels);


            //Validate if labels exist
            foreach ($json['labels'] ?? [] as $label) {
                if (in_array($label['name'], $labels)) {
                    $foundLabels ++;
                    $this->info('Found label ' . $label['name']);
                }
            }

            $this->info('Total labels found ' . $foundLabels);

            if ($foundLabels == $requiredLabels) {
                $this->info('Labels matched, proceeding');
                exit(0);
            }

            $this->error('Inconsistency in label amounts, failing');
            exit(1);
        }

        $this->error('No response from github, not proceeding');

        exit(2);
    }
}
