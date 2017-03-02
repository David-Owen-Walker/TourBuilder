<?php

class TourTable extends Omeka_Db_Table
{
	public function findItemsByTourId( $tour_id )
	{
		$db = get_db();
		$prefix=$db->prefix;
		$itemTable = $this->getTable( 'Item' );
		$select = $itemTable->getSelect();
		$iAlias = $itemTable->getTableAlias();
		$select->joinInner( array( 'ti' => $db->TourItem ),
			"ti.item_id = $iAlias.id", array() );
		$select->where( 'ti.tour_id = ?', array( $tour_id ) );
		$select->order( 'ti.ordinal ASC' );

		$items = $itemTable->fetchObjects( "SELECT i.*, ti.ordinal
         FROM ".$prefix."items i LEFT JOIN ".$prefix."tour_items ti
         ON i.id = ti.item_id
         WHERE ti.tour_id = ?
         ORDER BY ti.ordinal ASC",
			array( $tour_id ) );

		//$items = $itemTable->fetchObjects( $select );
		return $items;
	}

	public function findImageByTourId( $tour_id ) {
		$db = get_db();
		$prefix=$db->prefix;
		$itemTable = $this->getTable( 'File' );
		$select = $itemTable->getSelect();
		$iAlias = $itemTable->getTableAlias();
		$select->joinInner( array( 'ti' => $db->TourItem ),
			"ti.item_id = $iAlias.id", array() );
		$select->where( 'ti.tour_id = ?', array( $tour_id ) );
		$select->order( 'ti.ordinal ASC' );

		$items = $itemTable->fetchObjects( "SELECT f.*, ti.ordinal
         FROM ".$prefix."files f LEFT JOIN ".$prefix."tour_items ti
         ON i.id = ti.item_id
         WHERE ti.tour_id = ?
         ORDER BY ti.ordinal ASC",
			array( $tour_id ) );

		//$items = $itemTable->fetchObjects( $select );
		return $items;
	}

	public function getSelect()
	{
		$select = parent::getSelect()->order('tours.id');

		$permissions = new Omeka_Db_Select_PublicPermissions( 'TourBuilder_Tours' );
		$permissions->apply( $select, 'tours', null );

		if( ! is_allowed( 'TourBuilder_Tours', 'show-unpublished' ) )
		{
			// Determine public level TODO: May be outdated
			$select->where( $this->getTableAlias() . '.public = 1' );
		}

		return $select;
	}
	public function getSelectForFindBy($params = array())
    	{
        	$select = $this->getSelect();
		if ($params["near"]) {
			// Build an expression that evaluates to the SQUARE of the distance from the given point
			// Distance is measured in units of latitude (~111km) as though the earth were flat
			$point = json_decode($params["near"]);
			$latitude = $point->lat;
			$longitude = $point->lng;
			$dlat = "(loc.latitude - " . $latitude . ")";
			$scale = cos(deg2rad($latitude));
			$dlng = "((loc.longitude - " . $longitude . ")*".$scale.")";
			$distance = "(" . $dlat . "*" . $dlat . " + " . $dlng . "*" . $dlng . ")";

			$db = get_db();
			$alias = $this->getTableAlias();
			$select->join(array( "ti"=>$db->TourItem),
				"ti.ordinal = 0 AND ti.tour_id = " . $alias . ".id",
				array("item_id","ordinal","tour_id"));

			$select->join(array("loc"=>$db->Location), "loc.item_id = ti.item_id",array("distance"=> $distance));

			$select->reset( Zend_Db_Select::ORDER );

			$select->order("distance");
		}
		elseif ($sortParams) {
            		list($sortField, $sortDir) = $sortParams;
            		$this->applySorting($select, $sortField, $sortDir);
            		if ($select->getPart(Zend_Db_Select::ORDER)
                		&& $sortField != 'id'
            		) {
                		$alias = $this->getTableAlias();
                		$select->order("$alias.id $sortDir");
            		}
        	}
        	$this->applySearchFilters($select, $params);
        	return $select;
    	}
}
