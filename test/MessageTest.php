<?php
declare(strict_types=1);

namespace Thirdplace\Irc;

use PHPUnit\Framework\TestCase;

class MessageTest extends TestCase
{
    function testMessages()
    {
        // factory methods
        $this->assertEquals('USER bot 0 * :bot', Message::user('bot'));
        $this->assertEquals('JOIN #php', Message::join('#php'));
        $this->assertEquals('JOIN #php', Message::join(['#php']));
        $this->assertEquals('JOIN #php,#js', Message::join(['#php', '#js']));
        $this->assertEquals('PART #js', Message::part('#js'));
        $this->assertEquals('PART #js :goodbye', Message::part('#js', 'goodbye'));
        $this->assertEquals('PART #js', Message::part(['#js']));
        $this->assertEquals('PART #js,#php', Message::part(['#js', '#php']));
        // todo: assertEquals('PART #js,#php', Message::part(['#js', '#php']));
        $this->assertEquals('NICK bot', Message::nick('bot'));
        $this->assertEquals('PRIVMSG botfather :hello world', Message::privmsg('botfather', 'hello world'));
        $this->assertEquals('PRIVMSG botfather :hello world', Message::privmsg(['botfather'], 'hello world'));
        $this->assertEquals('PRIVMSG botfather,a :hello world', Message::privmsg(['botfather', 'a'], 'hello world'));
        $this->assertEquals('PRIVMSG #php :hello world', Message::privmsg('#php', 'hello world'));
        $this->assertEquals('NOTICE botfather :hello world', Message::notice('botfather', 'hello world'));
        $this->assertEquals('NOTICE botfather :hello world', Message::notice(['botfather'], 'hello world'));
        $this->assertEquals('NOTICE botfather,a :hello world', Message::notice(['botfather', 'a'], 'hello world'));
        $this->assertEquals('MODE botfather +iw', Message::mode('botfather', '+iw'));
        $this->assertEquals('MODE #thirdplace botfather +o', Message::mode('#thirdplace', 'botfather +o'));
        $this->assertEquals('TOPIC #thirdplace', Message::topic('#thirdplace'));
        $this->assertEquals('TOPIC #thirdplace :', Message::topic('#thirdplace', ''));
        $this->assertEquals('TOPIC #thirdplace :hello world', Message::topic('#thirdplace', 'hello world'));
        $this->assertEquals('KICK #thirdplace botfather :botfather', Message::kick('#thirdplace', 'botfather'));
        $this->assertEquals('KICK #thirdplace botfather :dont', Message::kick('#thirdplace', 'botfather', 'dont'));
        $this->assertEquals('INVITE botfather #thirdplace', Message::invite('botfather', '#thirdplace'));
        $this->assertEquals('ISON botfather', Message::ison('botfather'));
        $this->assertEquals('ISON botfather', Message::ison(['botfather']));
        $this->assertEquals('ISON botfather bob', Message::ison(['botfather', 'bob']));

        // convenience methods
        $this->assertEquals('hello world', Message::privmsg('#thirdplace', 'hello world')->getMessageParameter());
        $this->assertEquals('hello world', Message::privmsg('thirdplace', 'hello world')->getMessageParameter());

        $this->assertTrue(Message::privmsg('#php', 'hello')->isChannelMessage());
        $this->assertTrue(Message::privmsg('botfather', 'hello')->isDirectMessage());
    }
}
