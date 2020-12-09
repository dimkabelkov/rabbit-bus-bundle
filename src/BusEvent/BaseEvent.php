<?php


namespace Dimkabelkov\RabbitBusBundle\BusEvent;

use JMS\Serializer\Annotation as Serializer;
use Symfony\Contracts\EventDispatcher\Event;

abstract class BaseEvent extends Event
{
    public const EXCHANGE = null;

    public const NAME_PREFIX_PROBE = 'probe.';

    public const PROBE_LIVENESS  = 'liveness';
    public const PROBE_READINESS = 'readiness';

    public const PROBES = [
        self::PROBE_LIVENESS,
        self::PROBE_READINESS,
    ];

    /**
     * @var string
     * 
     * @Serializer\Type("string")
     * @Serializer\SerializedName("consumerTag")
     */
    public $consumerTag;

    /**
     * @var string
     * 
     * @Serializer\Type("string")
     * @Serializer\SerializedName("routingKey")
     */
    public $routingKey;

    /**
     * @var string
     * @Serializer\Type("string")
     * @Serializer\SerializedName("name")
     */
    public $name;

    /**
     * @var string
     * @Serializer\Type("string")
     * @Serializer\SerializedName("id")
     */
    public $id;

    /**
     * @var string
     * @Serializer\Type("string")
     * @Serializer\SerializedName("value")
     */
    public $value;

    /**
     * @param string $name
     * @param string $id
     * @param string $value
     */
    public function __construct(string $name = null, string $id = null, string $value = null)
    {
        $this->name  = $name;
        $this->id    = $id;
        $this->value = $value;
    }

    public function getProbeName()
    {
        return str_replace(static::NAME_PREFIX_PROBE, '', $this->name);
    }

    /**
     * @param $probe
     *
     * @return $this
     */
    public function makeProbe($probe)
    {
        if (!in_array($probe, self::PROBES)) {
            throw new \InvalidArgumentException('Unknown probe, name: ' . $probe);
        }

        $this->name = static::NAME_PREFIX_PROBE . $probe;
        $this->id   = md5(microtime());

        return $this;
    }

    public function isProbe()
    {
        return 0 === strpos($this->name, static::NAME_PREFIX_PROBE);
    }

    public function isProbeHost()
    {
        return 0 === strpos($this->name, static::NAME_PREFIX_PROBE) && strpos($this->routingKey, gethostname()) !== false;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return [
            'id'    => $this->id,
            'name'  => $this->name,
            'value' => $this->value,
        ];
    }
    
    public function setConsumerTag(string $consumerTag)
    {
        $this->consumerTag = $consumerTag;
    }
    
    public function setRoutingKey(string $routingKey)
    {
        $this->routingKey = $routingKey;
    }
}
