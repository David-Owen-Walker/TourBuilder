<?php
/**
 */
class Api_Tour extends Omeka_Record_Api_AbstractRecordAdapter
{
    /**
     * Get the REST representation of a tour.
     * 
     * @param Tour $record
     * @return array
     */
    public function getRepresentation(Omeka_Record_AbstractRecord $record)
    {

        $db = get_db();
        $tiTable = $db->getTable( 'TourItem' );

        $select = $tiTable->getSelect();
        $select->where( 'tour_id = ?', array( $record->id ) );

        # Get the tour items
        $tourItems = $tiTable->fetchObjects( $select );

        #map tour items to items
        $generator = function($tourItem){
            $result = array(
                'id' => $tourItem->item_id,
                'url' => $this->getResourceUrl("/items/{$tourItem->item_id}"),
                'resource' => 'items'
            );
            return $result;
        };

        $items = array_map($generator,$tourItems);

        $representation = array(
            'id' => $record->id,
            'url' => $this->getResourceUrl("/tours/{$record->id}"),
            'title' => $record->title,
            'description' => $record->description,
            'credits' => $record->credits,
            'public' => $record->public,
            'slug' => $record->slug,
            'postscript_text' => $record->postscript_text,
            'tour_image' => $record->tour_image,
            'items' => $items
        );
        return $representation;
    }
}
