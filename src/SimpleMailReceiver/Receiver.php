<?php
/**
 * @package
 * @subpackage
 * @author    Michael Nowag<michael.nowag@maviance.com>
 * @copyright maviance GmbH 2014
 */

namespace SimpleMailReceiver;

use SimpleMailReceiver\Commons\Collection;
use SimpleMailReceiver\Protocols\ProtocolFactory;
use SimpleMailReceiver\Protocols\ProtocolInterface;
use SimpleMailReceiver\Commons\Mailserver;

class Receiver
{

    /**
     * @var ProtocolInterface
     */
    private $protocol;

    /**
     * @var Mailserver
     */
    private $mailer;

    /**
     * @var Collection
     */
    private $config;

    /**
     * Get a parameter of the config or the entire collection
     *
     * @param bool $key The key of the parameter
     * @return Collection
     */
    final public function getConfig($key = false)
    {
        return $key ? $this->config->getItem( $key ) : $this->config;
    }

    /**
     * Set the config parameters
     *
     * @param $config
     * @return $this
     * @throws \InvalidArgumentException
     */
    final public function setConfig($config)
    {
        if ($config instanceof Collection) {
            $this->config = $config;
        } elseif (is_array($config)) {
            $this->config = new Collection($config);
        } else {
            throw new \InvalidArgumentException('Config must be an array or Collection');
        }

        return $this;
    }

    /**
     * Constructor
     *
     * @param array $config
     * @internal param array|\SimpleMailReceiver\Commons\Collection $configs The config of the app
     */
    public function __construct($config = null)
    {
        // is imap installed? Fail early!
        if (!in_array('imap', get_loaded_extensions())) {
            trigger_error(
                "It looks like you do not have imap installed.\n" .
                "IMAP is required to make request to the mail servers using the " .
                EmailConnectorConstants::USER_AGENT . " \n" . "library. For install instructions,
                visit the following page:\n" . "http://php.net/manual/en/imap.installation.php",
                E_USER_WARNING
            );
        }
        $this->setConfig($config ? : new Collection());
        $factory      = new ProtocolFactory();
        $this->protocol = $factory->create($this->getConfig('protocol'));
        $this->protocol->setMailServer($this->getConfig('host'))
            //Set the port
            ->setPort($this->getConfig('port'))
            //Set the main folder to retrieve mails
            ->setFolder($this->getConfig('folder'))
            //Set Secure Socket Layer to true or false
            ->setSsl($this->getConfig('ssl'));
    }

    /**
     * Connect to the mail Server
     */
    public function connect()
    {
        //Do the connection
        $this->mailer = new Mailserver(
            $this->protocol->connect(
                $this->getConfig('username'),
                $this->getConfig('password')
            )
        );
    }

    /**
     * Get all the mails in the inbox
     *
     * @return Collection
     */
    public function getMails()
    {
        return $this->mailer->retriveAllMails();
    }

    /**
     * Get the mail by id in the list of mails
     *
     * @param integer $id The id of the mail
     * @return null|Mail\Mail
     */
    public function getMail($id)
    {
        return $this->mailer->retriveMail($id);
    }

    /**
     * Get total number of all emails
     *
     * @return int
     */
    public function countAllMails()
    {
        return $this->mailer->countAllMails();
    }

    /**
     * Get total Number of unread emails
     *
     * @return int
     */
    public function countUnreadMails()
    {
        return $this->mailer->countUnreadMails();
    }

    /**
     * Close the connection
     *
     * @return bool
     */
    public function close()
    {
        return $this->mailer->close();
    }
}