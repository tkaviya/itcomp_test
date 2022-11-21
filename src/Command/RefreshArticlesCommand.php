<?php

namespace App\Command;

use App\Entity\Article;
use App\Repository\ArticleRepository;
use DateTime;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DomCrawler\Crawler;

/**
 * @AsCommand(name="app:refresh-articles")
 */
class RefreshArticlesCommand extends Command
{
    protected static $defaultName = 'app:refresh-articles';

    private static $NEWS_URL = "https://news.pindula.co.zw/";

    protected static $defaultDescription = "Refresh news articles from Pindula News.";

    private $articleRepository;

    //Thread pool executor for sending articles to Telegram in the background. Limit to 2 threads at a time
    //private $MAX_THREADS = 2;
    //private $pool;

    private $newArticleList = array();

    public function __construct(ArticleRepository $articleRepository)
    {
        $this->articleRepository = $articleRepository;
//        $this->pool = new Pool($this->MAX_THREADS);
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setHelp('This command refresh news articles from ' . RefreshArticlesCommand::$NEWS_URL . '...');
    }

    private function downloadNewsPage(): ?string
    {
        $ch = curl_init(RefreshArticlesCommand::$NEWS_URL);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);

        print("Downloading page " . RefreshArticlesCommand::$NEWS_URL . "...\r\n");

        $newPage = curl_exec($ch);

        if (curl_error($ch)) {
            die(curl_error($ch) . "\r\n");
        }

        print("Downloaded news html...\r\n");
        return $newPage;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $newsHTML = $this->downloadNewsPage();

        if (is_null($newsHTML) || empty($newsHTML)) {
            print("Could not get a valid HTML file from the remote server");
            return Command::FAILURE;
        }
        $crawler = new Crawler();
        $crawler->addHtmlContent($newsHTML);

        $articlesHTML = $crawler->filterXPath('//article');
        print("Currently have " . count($this->articleRepository->findAll()) . " articles\r\n");
        print("Downloaded " . $articlesHTML->count() . " articles\r\n");

        $countAdded = 0;

        foreach ($articlesHTML as $articleHTML) {

            $title = $articleHTML->childNodes->item(0)->attributes[1]->value;

            //check if article title is already in the DB
            if (count($this->articleRepository->findBy(array('title' => $title))) > 0) {
                //if this article has already been imported, dont bother parsing this article, next!
                continue;
            }

            //get picture
            $elementCrawler = new Crawler($articleHTML);
            $imgHTML = $elementCrawler->filterXPath('//img');
            $picture = $imgHTML->getNode(0)->attributes[2]->value;

            //get date
            $footerHTML = $articleHTML->childNodes->item(2)->childNodes->item(0);
            $date = trim($footerHTML->textContent);

            //get description
            $descCrawler = new Crawler($articleHTML->childNodes->item(2));
            $descHTML = $descCrawler->filterXPath('//a');
            $description = $descHTML->getNode(0)->textContent;

            $link = $articleHTML->childNodes->item(0)->attributes[0]->value;

            print("----------------------------\r\n");
            print("title : $title\r\n");
            print("pict. : $picture\r\n");
            print("date  : $date\r\n");
            print("desc. : $description\r\n");
            print("link. : $link\r\n");
            print("----------------------------\r\n");

            $article = new Article();
            $article->setTitle($title);
            $article->setDescription($description);
            $article->setPicture($picture);
            $article->setDate(DateTime::createFromFormat("F j, Y", $date));
            $article->setLink($link);
            $this->articleRepository->add($article, true);
            $countAdded++;

            //send article to Telegram Bot 'ITCompNews_bot'
            // @TODO use Thread pool executor to send data to Telegram
            //$this->pool->submit(new SendTelegramThread($article));

            array_push($this->newArticleList, $article);
        }

        foreach ($this->newArticleList as $newArticle) {
            $this->sendToTelegram($newArticle);
        }

        print("Added $countAdded new articles...\r\n");
        return Command::SUCCESS;
    }

    private function sendToTelegram(Article $article) {

        print("Sending article to Telegram Bot: '" . $article->getTitle() . "'\r\n");

        $ch = curl_init("https://api.telegram.org/5654863718:AAH97c93-nnZeNYQPYHCpRSEZeqzirlTfPg/sendMessage?chat_id=%40ITCompNews_bot&text=test");

        $data = "[
            'chat_id': '@ITCompNews_bot',
            'text': '" . $article->getTitle() . " | " . $article->getDate()->format("F j, Y") . "',
        ]";


        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);

        $newPage = curl_exec($ch);

        if (curl_error($ch)) {
            die(curl_error($ch) . "\r\n");
        }

        print("Sent article to Telegram\r\n");
        return $newPage;
    }

}