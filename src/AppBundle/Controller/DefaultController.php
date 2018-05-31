<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\DomCrawler\Crawler;
use AppBundle\Entity\Market;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Filesystem\Filesystem;

class DefaultController extends Controller
{

    private $marketsArray = array(
        "SHA:000001"=>array("exchange"=>"SHA","stock"=>"000001","name"=>"Shanghai","deviation"=>""),
        "INDEXNIKKEI:NI225"=>array("exchange"=>"INDEXNIKKEI","stock"=>"NI225","name"=>"Nikkei 225","deviation"=>0),
        "INDEXHANGSENG:HSI"=>array("exchange"=>"INDEXHANGSENG","stock"=>"HSI","name"=>"Hang Seng Index","deviation"=>0),
        "TPE:TAIEX"=>array("exchange"=>"TPE","stock"=>"TAIEX","name"=>"TSEC","deviation"=>0),
        "INDEXFTSE:UKX"=>array("exchange"=>"INDEXFTSE","stock"=>"UKX","name"=>"FTSE 100","deviation"=>0),
        "INDEXSTOXX:SX5E"=>array("exchange"=>"INDEXSTOXX","stock"=>"SX5E","name"=>"EURO STOXX 50","deviation"=>0),
        "INDEXEURO:PX1"=>array("exchange"=>"INDEXEURO","stock"=>"PX1","name"=>"CAC 40","deviation"=>0),
        "INDEXTSI:OSPTX"=>array("exchange"=>"INDEXTSI","stock"=>"OSPTX","name"=>"S&P TSX","deviation"=>0),
        "INDEXASX:XJO"=>array("exchange"=>"INDEXASX","stock"=>"XJO","name"=>"S&P/ASX 200","deviation"=>0),
        "INDEXBOM:SENSEX"=>array("exchange"=>"INDEXBOM","stock"=>"SENSEX","name"=>"BSE Sensex","deviation"=>0),
        "TLV:T25"=>array("exchange"=>"TLV","stock"=>"T25","name"=>"TA25","deviation"=>0),
        "INDEXSWX:SMI"=>array("exchange"=>"INDEXSWX","stock"=>"SMI","name"=>"SMI","deviation"=>0),
        "INDEXVIE:ATX"=>array("exchange"=>"INDEXVIE","stock"=>"ATX","name"=>"ATX","deviation"=>0),
        "INDEXBVMF:IBOV"=>array("exchange"=>"INDEXBVMF","stock"=>"IBOV","name"=>"IBOVESPA","deviation"=>0),
        "INDEXBKK:SET"=>array("exchange"=>"INDEXBKK","stock"=>"SET","name"=>"SET","deviation"=>0),
        "INDEXIST:XU100"=>array("exchange"=>"INDEXIST","stock"=>"XU100","name"=>"BIST100","deviation"=>0),
        "INDEXBME:IB"=>array("exchange"=>"INDEXBME","stock"=>"IB","name"=>"IBEX","deviation"=>0),
        "WSE:WIG"=>array("exchange"=>"WSE","stock"=>"WIG","name"=>"WIG","deviation"=>0),
        "TADAWUL:TASI"=>array("exchange"=>"TADAWUL","stock"=>"TASI","name"=>"TASI","deviation"=>0),
        "BCBA:IAR"=>array("exchange"=>"BCBA","stock"=>"IAR","name"=>"MERVAL","deviation"=>0),
        "INDEXBMV:ME"=>array("exchange"=>"INDEXBMV","stock"=>"ME","name"=>"IPC","deviation"=>0),
        "IDX:COMPOSITE"=>array("exchange"=>"IDX","stock"=>"COMPOSITE","name"=>"IDX Composite","deviation"=>0)
    );

    /**
     * @Route("/", name="markets")
     */
    public function indexAction(Request $request)
    {
        return $this->render('default/index.html.twig', [
        ]);
    }

    /**
     * @Route("/extract-markets", name="extract-markets")
     */
    public function extractMarketsAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();

        foreach($this->marketsArray as $key => $val) {
            $stock = $val['stock'];
            $exchange = $val['exchange'];
            $symbol = $key;

            $market = $em->getRepository(Market::class)->findBy(array('name' => $stock));

            if(!$market){
                $market = new Market();
                $market->setName($symbol);
            }

            $url = 'http://www.google.com/finance/getprices?q=' . $stock . '&x=' . $exchange . '&i=86400&p=5d&f=c&df=cpct&auto=0&ei=Ef6XUYDfCqSTiAKEMg';
            $obj = file_get_contents($url);

            if ($obj) {

                //explode to array and convert string values to int and filter by numeric...strips out instructional lines
                $lines = array_map('intval', array_filter(explode("\n", $obj), 'is_numeric'));

                //sum up values. there's only a single value being returned on each line so no need to split lines up
                $linesTotal = array_sum($lines);

                //count lines
                $linesCount = count($lines);

                //find mean as float val
                $mean = floatval($linesTotal / $linesCount);

                //loop through lines and add up
                $endSum = 0;
                foreach ($lines as $line) {
                    //devide each value by the mean
                    $val = $line - $mean;
                    //square new values
                    $endSum += $val * $val;
                }

                //devide endsum by item count -1, get sqrt and round
                $endSum = round(sqrt($endSum / ($linesCount - 1)), 2);

                //last value in lines...is todays
                //$todaysClosing = end($lines);

                $market->setValue($endSum);
                $market->setMarketIndex('none');
                $em->persist($market);
            }
        }

        $em->flush();

        $records = $em->getRepository(Market::class)->findAll();

        return $this->render('default/index.html.twig', [
            'records' => $records,
        ]);
    }

    /**
     * @Route("/extract-indexes", name="extract-indexes")
     */
    public function extractIndexesAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();

        foreach($this->marketsArray as $key => $val){
            //get url for this symbol
            $infoUrl = 'http://www.google.com/finance/info?client=ig&q='.$key;
            //get contents, remove //
            $infoObj = str_replace('//','',file_get_contents($infoUrl));

            if($infoObj){
                //clean control chars and convert to asc array
                $infoObj = json_decode(utf8_encode($infoObj),true);

                $exchange = $infoObj[0]['e'];
                $cPercentage = $infoObj[0]['cp'];//this is the percentage, which is basically just comparing the change between the end of the last day, and the most recent transaction
                $prevClosePrice = floatval(str_replace(',','',$infoObj[0]['pcls_fix']));
                $lastTradePrice = floatval(str_replace(',','',$infoObj[0]['l']));

                //mean
                $mean = ($prevClosePrice+$lastTradePrice)/2;

                //subtract mean from each, and square
                $newPrev = ($prevClosePrice-$mean)*($prevClosePrice-$mean);
                $newLast = ($lastTradePrice-$mean)*($lastTradePrice-$mean);

                //sum/devide by 1 (so nothing) and sqrt
                $final = round(sqrt($newPrev+$newLast),2);

                $market = $em->getRepository(Market::class)->findBy(array('name' => $stock));

                if(!$market){
                    $market = new Market();
                    $market->setName($key);
                }

                $market->setValue($final);
                $market->setIndex($cPercentage);
                $em->persist($market);
            }
        }

        $em->flush();

        $records = $em->getRepository(Market::class)->findAll();

        return $this->render('default/index.html.twig', [
            'records' => $records,
        ]);
    }

    /**
     * @Route("/send-xml", name="send-xml")
     */
    public function sendXmlAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();

        $records = $em->getRepository(Market::class)->findAll();
        $this->sendEmail($records);

        return $this->render('default/index.html.twig', [
            'records' => $records
        ]);
    }

    protected function sendEmail($records){
        $encoders = array(new XmlEncoder());
        $normalizers = array(new ObjectNormalizer());

        $serializer = new Serializer($normalizers, $encoders);

        $xml = $serializer->serialize($records, 'xml');
        $path = '/tmp/info.xml';

        $fs = new Filesystem();
        $fs->dumpFile($path, $xml);

        $mailer = $this->container->get('swiftmailer.mailer');

        $message = (new \Swift_Message('Hello Email'))
            ->setFrom('oleksiy.perepelytsya@gmail.com')
            ->setTo('sandis@monify.lv')
            ->setCc('eyyub@learn-solve.com')
            ->setBody(
                $this->render(
                // app/Resources/views/Emails/information.html.twig
                    'Emails\information.html.twig',
                    array('records' => $records)
                ),
                'text/html'
            )

        ;

        $message->attach(
            \Swift_Attachment::fromPath($path)->setFilename('info.xml')
        );

        return $mailer->send($message);
    }

}
