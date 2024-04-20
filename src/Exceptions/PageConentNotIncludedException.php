<?php

namespace Ominity\Laravel\Exceptions;

use DateTime;

class PageContentNotLoadedException extends \Exception
{
    /**
     * @var string
     */
    protected $plainMessage;

    /**
     * ISO8601 representation of the moment this exception was thrown
     *
     * @var \DateTimeImmutable
     */
    protected $raisedAt;

    /**
     * @param string $message
     * @param \Throwable|null $previous
     */
    public function __construct(
        $message = "", 
        $code = 0,
        $previous = null
    ) {
        $this->plainMessage = $message;
        $this->raisedAt = new  \DateTimeImmutable();
        $formattedRaisedAt = $this->raisedAt->format(DateTime::ISO8601);

        $enhancedMessage = "[{$formattedRaisedAt}] " . $message;

        parent::__construct($enhancedMessage, $code, $previous);
    }

    /**
     * Get the ISO8601 representation of the moment this exception was thrown
     *
     * @return DateTimeImmutable
     */
    public function getRaisedAt()
    {
        return $this->raisedAt;
    }

    /**
     * Retrieve the plain exception message.
     *
     * @return string
     */
    public function getPlainMessage()
    {
        return $this->plainMessage;
    }
}
