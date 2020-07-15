<?php
namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * Rating Entity
 *
 * @property int $id
 * @property int $from_user_id
 * @property int $to_user_id
 * @property int $biditem_id
 * @property int $stars
 * @property string $comment
 * @property \Cake\I18n\Time $updated_at
 * @property \Cake\I18n\Time $updated
 * @property \Cake\I18n\Time $created_at
 * @property \Cake\I18n\Time $created
 *
 * @property \App\Model\Entity\FromUser $from_user
 * @property \App\Model\Entity\ToUser $to_user
 * @property \App\Model\Entity\Biditem $biditem
 */
class Rating extends Entity
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
        'from_user_id' => true,
        'to_user_id' => true,
        'biditem_id' => true,
        'stars' => true,
        'comment' => true,
        'updated' => true,
        'created' => true,
        'from_user' => true,
        'to_user' => true,
        'biditem' => true,
    ];
}
