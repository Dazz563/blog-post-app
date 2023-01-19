import './bootstrap';
import Search from './live-search';

// only loads the search feature if the class exists on the current page
if (document.querySelector('.header-search-icon')) {
	new Search();
}
