<?php

require_once 'vendor/autoload.php';

use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;

error_reporting(E_ALL);
ini_set('display_errors', 1);

(new Dotenv\Dotenv(__DIR__))->load();

$command = function (InputInterface $input, OutputInterface $output) {

    $fb = new \Facebook\Facebook([
        'default_graph_version' => 'v2.10',
        'default_access_token' => getenv('FACEBOOK_APP_ID').'|'.getenv('FACEBOOK_APP_SECRET'),
    ]);

    $graphPage = $fb->get('/'.$input->getArgument('page_id'))->getGraphPage();
    $output->writeln('<info>Fetching albums for "'.$graphPage->getName().'"...</info>'.PHP_EOL);

    $response = $fb->get('/'.$input->getArgument('page_id').'/albums?fields=count,name');
    $answers = [];
    $albums = [];

    foreach ($response->getDecodedBody()['data'] as $album) {
        $answers[$album['id']] = $album['name'].' ('.$album['count'].')';
        $albums[$album['id']] = $album;
    }

    $question = new ChoiceQuestion('Please select album to download:', array_values($answers));
    $albumId = array_search($this->getHelper('question')->ask($input, $output, $question), $answers);

    (new \Symfony\Component\Filesystem\Filesystem())->mkdir('downloaded/'.$albumId);

    $albumDetails = $fb->get('/' . $albumId . '/photos?fields=id,images,link');
    $edge = $albumDetails->getGraphEdge();

    $count = 0;

    $output->writeln(PHP_EOL.'<info>Found '.$albums[$albumId]['count'].' images to download.</info>');

    do {
        $iterator = $edge->getIterator();

        /** @var \Facebook\GraphNodes\GraphNode $photo */
        foreach ($iterator as $photo) {
            $url = $photo->getField('images')[0]['source'];

            $output->writeln(str_pad(++$count, strlen($albums[$albumId]['count']), ' ', STR_PAD_LEFT)
                .'/'.$albums[$albumId]['count'].' - '.strtok($url, '?'));

            $pathInfo = pathinfo(strtok($url, '?'));

            $targetFile = 'downloaded/'.$albumId.'/'
                .($input->getOption('rename') ? $count.'.'.$pathInfo['extension'] : $pathInfo['basename']);

            file_put_contents($targetFile, fopen($url, 'r'));
        }

        $edge = $fb->next($edge);

        if (!$edge) {
            break;
        }
        $iterator = $edge->getIterator();

    } while ($iterator->valid());

    $output->writeln(PHP_EOL.'<info>Successfully downloaded '.$count.' photo'.($count > 1 ? 's' : '').' to "'
        .getcwd().'/downloaded/'.$albumId.'/'.'".</info>');
};

(new Application())
    ->register('fb:album:download')
    ->addArgument('page_id', InputArgument::REQUIRED, 'Page ID.')
    ->addOption('rename', null, InputOption::VALUE_NONE)
    ->setCode($command)
    ->getApplication()
    ->setDefaultCommand('fb:album:download', true)
    ->run();