function elemsHorizontalOverlap(elem1, elem2) {
	var elem1 = $(elem1);
	var elem1_right = elem1.offset().left + elem1.outerWidth()
	var offset = elem1_right - $(elem2).offset().left;
	return (offset > 0);
}