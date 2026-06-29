<?php

namespace SV\StandardLib\XF\Data;

use function array_keys;
use function array_map;
use function implode;
use function preg_match;
use function strtolower;

/**
 * @extends \XF\Data\Robot
 */
class Robot extends XFCP_Robot
{
    public function getRobotUserAgents()
    {
        return [
            'adsbot-google' => 'google-adsbot',
            'ahrefsbot' => 'ahrefs',
            'aiohttp/' => 'http-client',
            'amazonbot' => 'amazonbot',
            'applebot' => 'applebot',
            'archive.org_bot' => 'archive.org',
            'awariobot' => 'awario',
            'baiduspider' => 'baidu',
            'better uptime' => 'betterstack',
            'betterstack' => 'betterstack',
            'bingbot' => 'bing',
            'blexbot' => 'blexbot',
            'bluesky' => 'bluesky',
            'bytespider' => 'bytedance',
            'ccbot' => 'commoncrawl',
            'chatgpt-user' => 'openai-chatgpt',
            'checkly' => 'checkly',
            'claude-searchbot' => 'anthropic-search',
            'claude-user' => 'anthropic-user',
            'claudebot' => 'anthropic',
            'cohere-ai' => 'cohere',
            'curl/' => 'http-client',
            'dataforseobot' => 'dataforseo',
            'diffbot' => 'diffbot',
            'discordbot' => 'discord',
            'dotbot' => 'dotbot',
            'duckassistbot' => 'duckduckgo-assist',
            'duckduckbot' => 'duckduckgo',
            'facebookexternalhit' => 'facebookextern',
            'go-http-client/' => 'http-client',
            'googlebot' => 'google',
            'google-inspectiontool' => 'google-inspectiontool',
            'googleother' => 'google-other',
            'gptbot' => 'openai',
            'headlesschrome/' => 'headless-browser',
            'ia_archiver' => 'alexa',
            'imagesiftbot' => 'imagesift',
            'linkedinbot' => 'linkedin',
            'ltx71' => 'ltx71',
            'magpie-crawler' => 'brandwatch',
            'marginalia' => 'marginalia',
            'mastodon/' => 'mastodon',
            'mauibot' => 'mauibot',
            'mediapartners-google' => 'google-adsense',
            'meta-externalagent' => 'meta-externalagent',
            'mistralai-user' => 'mistral',
            'mj12bot' => 'mj12',
            'msnbot' => 'msnbot',
            'oai-searchbot' => 'openai-search',
            'okhttp/' => 'http-client',
            'omgili' => 'omgili',
            'perplexity-user' => 'perplexity-user',
            'perplexitybot' => 'perplexity',
            'petalbot' => 'petalsearch',
            'pingdom' => 'pingdom',
            'pinterestbot' => 'pinterest',
            'proximic' => 'proximic',
            'python-httpx/' => 'http-client',
            'python-requests/' => 'http-client',
            'redditbot' => 'reddit',
            'scoutjet' => 'scoutjet',
            'scrapy/' => 'http-client',
            'seekportbot' => 'seekport',
            'semrushbot' => 'semrush',
            'seostar' => 'seostar',
            'seznambot' => 'seznam',
            'site24x7' => 'site24x7',
            'slackbot' => 'slack',
            'sogou web spider' => 'sogou',
            'statuscake' => 'statuscake',
            'telegrambot' => 'telegram',
            'timpibot' => 'timpi',
            'trendictionbot' => 'trendiction',
            'twitterbot' => 'twitter',
            'uptimerobot' => 'uptimerobot',
            'wget/' => 'http-client',
            'whatsapp/' => 'whatsapp',
            'yahoo! slurp' => 'yahoo',
            'yandex' => 'yandex',
            'youbot' => 'you',
        ];
    }

    public function userAgentMatchesRobot($userAgent)
    {
        $bots = $this->getRobotUserAgents();

        if (preg_match(
            '#(' . implode('|', array_map('preg_quote', array_keys($bots))) . ')#i',
            strtolower($userAgent),
            $match
        ))
        {
            return $bots[$match[1]];
        }
        else
        {
            return '';
        }
    }

    public function getRobotList()
    {
        return [
            'ahrefs' => [
                'title' => 'Ahrefs',
                'link' => 'http://ahrefs.com/robot/',
            ],
            'alexa' => [
                'title' => 'Alexa',
                'link' => 'http://www.alexa.com/help/webmasters',
            ],
            'amazonbot' => [
                'title' => 'Amazon',
                'link' => 'https://developer.amazon.com/support/amazonbot',
            ],
            'anthropic' => [
                'title' => 'Anthropic',
                'link' => 'https://www.anthropic.com/',
            ],
            'anthropic-search' => [
                'title' => 'Anthropic Claude-SearchBot',
                'link' => 'https://www.anthropic.com/',
            ],
            'anthropic-user' => [
                'title' => 'Anthropic Claude-User',
                'link' => 'https://www.anthropic.com/',
            ],
            'applebot' => [
                'title' => 'Applebot',
                'link' => 'https://support.apple.com/en-us/HT204683',
            ],
            'archive.org' => [
                'title' => 'Internet Archive',
                'link' => 'http://www.archive.org/details/archive.org_bot',
            ],
            'awario' => [
                'title' => 'Awario',
                'link' => 'https://awario.com/bots.html',
            ],
            'baidu' => [
                'title' => 'Baidu',
                'link' => 'http://www.baidu.com/search/spider.htm',
            ],
            'betterstack' => [
                'title' => 'Better Stack',
                'link' => 'https://betterstack.com/',
            ],
            'bing' => [
                'title' => 'Bing',
                'link' => 'http://www.bing.com/bingbot.htm',
            ],
            'blexbot' => [
                'title' => 'BLEXBot',
                'link' => 'http://webmeup-crawler.com/',
            ],
            'bluesky' => [
                'title' => 'Bluesky',
                'link' => 'https://bsky.app/',
            ],
            'brandwatch' => [
                'title' => 'Brandwatch',
                'link' => 'http://www.brandwatch.com/how-it-works/gathering-data/',
            ],
            'bytedance' => [
                'title' => 'ByteDance',
                'link' => 'https://www.bytedance.com/',
            ],
            'checkly' => [
                'title' => 'Checkly',
                'link' => 'https://www.checklyhq.com/',
            ],
            'cohere' => [
                'title' => 'Cohere',
                'link' => 'https://cohere.com/',
            ],
            'commoncrawl' => [
                'title' => 'Common Crawl',
                'link' => 'https://commoncrawl.org/ccbot',
            ],
            'dataforseo' => [
                'title' => 'DataForSEO',
                'link' => 'https://dataforseo.com/dataforseo-bot',
            ],
            'diffbot' => [
                'title' => 'Diffbot',
                'link' => 'https://www.diffbot.com/',
            ],
            'discord' => [
                'title' => 'Discord',
                'link' => 'https://discord.com/',
            ],
            'dotbot' => [
                'title' => 'Moz Dotbot',
                'link' => 'https://moz.com/help/moz-procedures/crawlers/dotbot',
            ],
            'duckduckgo' => [
                'title' => 'DuckDuckGo',
                'link' => 'https://duckduckgo.com/duckduckbot',
            ],
            'duckduckgo-assist' => [
                'title' => 'DuckDuckGo DuckAssist',
                'link' => 'https://duckduckgo.com/duckassistbot',
            ],
            'facebookextern' => [
                'title' => 'Facebook',
                'link' => 'http://www.facebook.com/externalhit_uatext.php',
            ],
            'google' => [
                'title' => 'Google',
                'link' => 'https://support.google.com/webmasters/answer/182072',
            ],
            'google-adsbot' => [
                'title' => 'Google Ads',
                'link' => 'http://www.google.com/adsbot.html',
            ],
            'google-adsense' => [
                'title' => 'Google AdSense',
                'link' => 'https://support.google.com/webmasters/answer/182072',
            ],
            'google-inspectiontool' => [
                'title' => 'Google Inspection Tool',
                'link' => 'https://developers.google.com/search/docs/crawling-indexing/google-common-crawlers#google-inspectiontool',
            ],
            'google-other' => [
                'title' => 'GoogleOther',
                'link' => 'https://developers.google.com/search/docs/crawling-indexing/google-common-crawlers#googleother',
            ],
            'headless-browser' => [
                'title' => 'Headless browser',
                'link' => '',
            ],
            'http-client' => [
                'title' => 'HTTP client library',
                'link' => '',
            ],
            'imagesift' => [
                'title' => 'ImageSift',
                'link' => 'http://imagesift.com/about',
            ],
            'linkedin' => [
                'title' => 'LinkedIn',
                'link' => 'https://www.linkedin.com/',
            ],
            'ltx71' => [
                'title' => 'LTX71',
                'link' => 'http://ltx71.com/',
            ],
            'marginalia' => [
                'title' => 'Marginalia',
                'link' => 'https://search.marginalia.nu/',
            ],
            'mastodon' => [
                'title' => 'Mastodon',
                'link' => 'https://joinmastodon.org/',
            ],
            'mauibot' => [
                'title' => 'MauiBot',
                'link' => '',
            ],
            'meta-externalagent' => [
                'title' => 'Meta External Agent',
                'link' => 'https://developers.facebook.com/docs/sharing/webmasters/web-crawlers',
            ],
            'mistral' => [
                'title' => 'Mistral AI',
                'link' => 'https://mistral.ai/',
            ],
            'mj12' => [
                'title' => 'Majestic-12',
                'link' => 'http://majestic12.co.uk/bot.php',
            ],
            'msnbot' => [
                'title' => 'MSN',
                'link' => 'http://search.msn.com/msnbot.htm',
            ],
            'omgili' => [
                'title' => 'Omgili (Webz.io)',
                'link' => 'https://webz.io/bot.html',
            ],
            'openai' => [
                'title' => 'OpenAI GPTBot',
                'link' => 'https://platform.openai.com/docs/bots',
            ],
            'openai-chatgpt' => [
                'title' => 'OpenAI ChatGPT-User',
                'link' => 'https://platform.openai.com/docs/bots',
            ],
            'openai-search' => [
                'title' => 'OpenAI SearchBot',
                'link' => 'https://platform.openai.com/docs/bots',
            ],
            'perplexity' => [
                'title' => 'Perplexity',
                'link' => 'https://docs.perplexity.ai/guides/bots',
            ],
            'perplexity-user' => [
                'title' => 'Perplexity User',
                'link' => 'https://docs.perplexity.ai/guides/bots',
            ],
            'petalsearch' => [
                'title' => 'Petal Search',
                'link' => 'https://webmaster.petalsearch.com/site/petalbot',
            ],
            'pingdom' => [
                'title' => 'Pingdom',
                'link' => 'https://www.pingdom.com/',
            ],
            'pinterest' => [
                'title' => 'Pinterest',
                'link' => 'https://www.pinterest.com/',
            ],
            'proximic' => [
                'title' => 'Proximic',
                'link' => 'http://www.proximic.com/info/spider.php',
            ],
            'reddit' => [
                'title' => 'Reddit',
                'link' => 'https://www.reddit.com/',
            ],
            'scoutjet' => [
                'title' => 'Blekko',
                'link' => 'http://www.scoutjet.com/',
            ],
            'seekport' => [
                'title' => 'Seekport',
                'link' => 'http://seekport.com/',
            ],
            'semrush' => [
                'title' => 'SEMRush',
                'link' => 'http://www.semrush.com/bot.html',
            ],
            'seostar' => [
                'title' => 'Seostar',
                'link' => 'https://seostar.co/robot/',
            ],
            'seznam' => [
                'title' => 'Seznam',
                'link' => 'https://napoveda.seznam.cz/en/seznamcz-web-search/',
            ],
            'site24x7' => [
                'title' => 'Site24x7',
                'link' => 'https://www.site24x7.com/',
            ],
            'slack' => [
                'title' => 'Slack',
                'link' => 'https://api.slack.com/robots',
            ],
            'sogou' => [
                'title' => 'Sogou',
                'link' => 'http://www.sogou.com/docs/help/webmasters.htm#07',
            ],
            'statuscake' => [
                'title' => 'StatusCake',
                'link' => 'https://www.statuscake.com/',
            ],
            'telegram' => [
                'title' => 'Telegram',
                'link' => 'https://core.telegram.org/bots',
            ],
            'timpi' => [
                'title' => 'Timpi',
                'link' => 'https://timpi.io/',
            ],
            'trendiction' => [
                'title' => 'Trendiction',
                'link' => 'https://www.trendiction.com/bot',
            ],
            'twitter' => [
                'title' => 'Twitter',
                'link' => 'https://developer.twitter.com/en/docs/twitter-for-websites/cards/guides/getting-started',
            ],
            'unknown' => [
                'title' => 'Unknown',
                'link' => '',
            ],
            'uptimerobot' => [
                'title' => 'UptimeRobot',
                'link' => 'https://uptimerobot.com/',
            ],
            'whatsapp' => [
                'title' => 'WhatsApp',
                'link' => 'https://www.whatsapp.com/',
            ],
            'yahoo' => [
                'title' => 'Yahoo',
                'link' => 'http://help.yahoo.com/help/us/ysearch/slurp',
            ],
            'yandex' => [
                'title' => 'Yandex',
                'link' => 'http://help.yandex.com/search/?id=1112030',
            ],
            'you' => [
                'title' => 'You.com',
                'link' => 'https://about.you.com/youbot/',
            ],
        ];
    }
}