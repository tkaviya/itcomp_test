<?php


namespace App\Threads;

use App\Entity\Article;
use Threaded;

class SendTelegramThread extends Threaded
{
    private $article;
    private $output;


    public function __construct(Article $article)
    {
        $this->article = $article;
        $this->output = new Threaded();
    }


    public function run()
    {
        print("Sending article to Telegram Bot: '" . $this->article->getTitle() . "'\r\n");

        $ch = curl_init("https://api.telegram.org/5654863718:AAH97c93-nnZeNYQPYHCpRSEZeqzirlTfPg/sendMessage?chat_id=%40ITCompNews_bot&text=test");

        $data = "[
            'chat_id': '@ITCompNews_bot',
            'text': '" . $this->article->getTitle() . " | " . $this->article->getDate()->format("F j, Y") . "',
        ]";


        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);

        $newPage = curl_exec($ch);

        if (curl_error($ch)) {
            die(curl_error($ch) . "\r\n");
        }

        print("----------\r\nSent article to Telegram:\r\n$newPage\r\n-----------\r\n");
        $this->output[] = (array)array(
            'response' => $newPage
        );
    }

    public function getResults() { return $this->output; }
}
