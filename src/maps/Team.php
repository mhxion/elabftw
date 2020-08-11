<?php
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
declare(strict_types=1);

namespace Elabftw\Maps;

use Defuse\Crypto\Crypto;
use Defuse\Crypto\Key;
use Elabftw\Elabftw\Db;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Interfaces\MapInterface;
use Elabftw\Services\Check;
use Elabftw\Services\Filter;
use PDO;

/**
 * One team
 */
class Team implements MapInterface
{
    /** @var Db $Db */
    private $Db;

    /** @var int $id */
    private $id;

    /** @var string $name */
    private $name;

    /** @var int $deletableXp */
    private $deletableXp;

    /** @var int $publicDb */
    private $publicDb;

    /** @var string $linkName */
    private $linkName = 'Documentation';

    /** @var string $linkHref */
    private $linkHref = 'https://doc.elabftw.net';

    /** @var string|null $stamplogin */
    private $stamplogin;

    /** @var string|null $stamppass */
    private $stamppass;

    /** @var string|null $stampprovider url for the team's timestamping provider */
    private $stampprovider;

    /** @var string|null $stampcert path to the cert for the team's timestamping provider */
    private $stampcert;

    /** @var string|null $orgid */
    private $orgid;

    /** @var int $doForceCanread */
    private $doForceCanread;

    /** @var int $doForceCanwrite */
    private $doForceCanwrite;

    /** @var string $forceCanread */
    private $forceCanread;

    /** @var string $forceCanwrite */
    private $forceCanwrite;

    /**
     * Constructor
     *
     */
    public function __construct(int $id)
    {
        $this->id = $id;
        $this->Db = Db::getConnection();
        $this->hydrate();
    }

    final public function setName(?string $setting): void
    {
        if ($setting === null) {
            throw new ImproperActionException('Team name cannot be empty!');
        }
        $this->name = $setting;
    }

    final public function getName(): string
    {
        return $this->name;
    }

    final public function setDeletableXp(string $setting): void
    {
        $this->deletableXp = Filter::toBinary($setting);
    }

    final public function getDeletableXp(): int
    {
        return $this->deletableXp;
    }

    final public function setPublicDb(string $setting): void
    {
        $this->publicDb = Filter::toBinary($setting);
    }

    final public function setLinkName(string $setting): void
    {
        $this->linkName = Filter::sanitize($setting);
    }

    final public function getLinkName(): string
    {
        return $this->linkName;
    }

    final public function setLinkHref(string $url): void
    {
        if (filter_var($url, FILTER_VALIDATE_URL) === false) {
            throw new ImproperActionException('Link target is not a valid URL');
        }
        $this->linkHref = $url;
    }

    final public function getLinkHref(): string
    {
        return $this->linkHref;
    }

    final public function setDoForceCanread(string $setting): void
    {
        $this->doForceCanread = Filter::toBinary($setting);
    }

    final public function getDoForceCanread(): int
    {
        return $this->doForceCanread;
    }

    final public function getDoForceCanwrite(): int
    {
        return $this->doForceCanwrite;
    }

    final public function getForceCanread(): string
    {
        return $this->forceCanread;
    }

    final public function getForceCanwrite(): string
    {
        return $this->forceCanwrite;
    }

    final public function setDoForceCanwrite(string $setting): void
    {
        $this->doForceCanwrite = Filter::toBinary($setting);
    }

    final public function setForceCanread(string $setting): void
    {
        $this->forceCanread = Check::visibility($setting);
    }

    final public function setForceCanwrite(string $setting): void
    {
        $this->forceCanwrite = Check::visibility($setting);
    }

    final public function setStamplogin(?string $setting): void
    {
        if (!empty($setting)) {
            $this->stamplogin = Filter::sanitize($setting);
        }
    }

    final public function setStamppass(string $setting): void
    {
        $this->stamppass = Crypto::encrypt($setting, Key::loadFromAsciiSafeString(\SECRET_KEY));
    }

    final public function setStampcert(?string $setting): void
    {
        if (!empty($setting)) {
            $this->stampcert = Filter::sanitize($setting);
        }
    }

    final public function setStampprovider(?string $url): void
    {
        if (!empty($url)) {
            if (filter_var($url, FILTER_VALIDATE_URL) === false) {
                throw new ImproperActionException('Timestamping provider is not a valid URL');
            }
            $this->stampprovider = $url;
        }
    }

    final public function setOrgid(?string $setting): void
    {
        if ($setting !== null) {
            $this->orgid = Filter::sanitize($setting);
        }
    }

    public function save(): bool
    {
        $sql = 'UPDATE teams SET
            name = :name,
            orgid = :orgid,
            deletable_xp = :deletable_xp,
            public_db = :public_db,
            link_name = :link_name,
            link_href = :link_href,
            do_force_canread = :do_force_canread,
            force_canread = :force_canread,
            do_force_canwrite = :do_force_canwrite,
            force_canwrite = :force_canwrite,
            stamplogin = :stamplogin,
            stamppass = :stamppass,
            stampprovider = :stampprovider,
            stampcert = :stampcert
            WHERE id = :id';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':name', $this->name);
        $req->bindParam(':orgid', $this->orgid);
        $req->bindParam(':deletable_xp', $this->deletableXp, PDO::PARAM_INT);
        $req->bindParam(':public_db', $this->publicDb, PDO::PARAM_INT);
        $req->bindParam(':link_name', $this->linkName);
        $req->bindParam(':link_href', $this->linkHref);
        $req->bindParam(':do_force_canread', $this->doForceCanread, PDO::PARAM_INT);
        $req->bindParam(':do_force_canwrite', $this->doForceCanwrite, PDO::PARAM_INT);
        $req->bindParam(':force_canread', $this->forceCanread);
        $req->bindParam(':force_canwrite', $this->forceCanwrite);
        $req->bindParam(':stamplogin', $this->stamplogin);
        $req->bindParam(':stamppass', $this->stamppass);
        $req->bindParam(':stampprovider', $this->stampprovider);
        $req->bindParam(':stampcert', $this->stampcert);
        $req->bindParam(':id', $this->id, PDO::PARAM_INT);

        return $this->Db->execute($req);
    }

    /**
     * Read from the current team
     *
     * @return array
     */
    private function read(): array
    {
        $sql = 'SELECT * FROM `teams` WHERE id = :id';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':id', $this->id, PDO::PARAM_INT);
        $this->Db->execute($req);

        $res = $req->fetch();
        if ($res === false) {
            throw new ImproperActionException('Could not find a team with that id!');
        }

        return $res;
    }

    private function hydrate(): void
    {
        $team = $this->read();
        $this->setName($team['name']);
        $this->setOrgid($team['orgid']);
        $this->setDeletableXp($team['deletable_xp']);
        $this->setLinkName($team['link_name']);
        $this->setLinkHref($team['link_href']);
        $this->setStamplogin($team['stamplogin']);
        $this->stamppass = $team['stamppass'];
        $this->stampprovider = $team['stampprovider'];
        $this->setStampcert($team['stampcert']);
        $this->setPublicDb($team['public_db']);
        $this->setDoForceCanread($team['do_force_canread'] ?? '');
        $this->setDoForceCanwrite($team['do_force_canwrite'] ?? '');
        $this->setForceCanread($team['force_canread']);
        $this->setForceCanwrite($team['force_canwrite']);
    }
}