<?php
namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * Delivery Entity
 *
 * @property int $id
 * @property int $biditem_id
 * @property int $user_id
 * @property string $name
 * @property string $address
 * @property string $phone
 * @property int $is_shipped
 * @property int $is_received
 * @property \Cake\I18n\Time $updated
 * @property \Cake\I18n\Time $created
 *
 * @property \App\Model\Entity\Biditem $biditem
 * @property \App\Model\Entity\User $user
 */
class Delivery extends Entity
{
    /**
     * Fields that can be mass assigned using newEntity() or patchEntity().
     *
     * Note that when '*' is set to true, this allows all unspecified fields to
     * be mass assigned. For security purposes, it is advised to set '*' to false
     * (or remove it), and explicitly make individual fields accessible as needed.
     *
     * @var array
     */
    protected $_accessible = [
        'biditem_id' => true,
        'user_id' => true,
        'name' => true,
        'address' => true,
        'phone' => true,
        'is_shipped' => true,
        'is_received' => true,
        'updated' => true,
        'created' => true,
        'biditem' => true,
        'user' => true,
    ];
}
