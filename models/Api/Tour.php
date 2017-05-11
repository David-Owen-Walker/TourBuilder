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
        $glTable = $db->getTable( 'Location' );

        $tiSelect = $tiTable->getSelect();
        $tiSelect->where( 'tour_id = ?', array( $record->id ) );

        # Get the tour items
        $tourItems = $tiTable->fetchObjects( $tiSelect );

        $glSelect = $glTable->getSelect();
        $glSelect->where( 'item_id = ?', array( $tourItems[0]->item_id ) );
        $geolocations = $glTable->fetchObjects( $glSelect );
        $startLocation = $geolocations[0];

        #map tour items to items
        $itemGenerator = function($tourItem){
            $result = array(
                'id' => $tourItem->item_id,
                'url' => $this->getResourceUrl("/items/{$tourItem->item_id}"),
                'resource' => 'items'
            );
            return $result;
        };

        $items = array_map($itemGenerator,$tourItems);

        # create an array of directions
        $directionGenerator = function($tourItem){
            $result = $tourItem->directions_to_item;
            return $result;
        };

        $directions = array_map($directionGenerator,$tourItems);

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
            'items' => $items,
            'start' => $startLocation
        );
        return $representation;
    }
}
