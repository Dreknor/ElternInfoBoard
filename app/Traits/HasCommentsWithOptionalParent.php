<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Model;

/**
 * Überschreibt die comment() Methode aus HasComments Trait,
 * um den $parent Parameter optional zu machen.
 *
 * Dieses Trait muss zusammen mit HasComments verwendet werden.
 */
trait HasCommentsWithOptionalParent
{
    /**
     * Create a comment.
     *
     * @param array      $data
     * @param Model      $creator
     * @param Model|null $parent
     *
     * @return mixed
     */
    public function comment(array $data, Model $creator, ?Model $parent = null)
    {
        $commentableModel = $this->commentableModel();

        $comment = (new $commentableModel())->createComment($this, $data, $creator);

        if (!empty($parent)) {
            $parent->appendNode($comment);
        }

        return $comment;
    }
}


