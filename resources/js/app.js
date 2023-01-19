import './bootstrap';
import Search from './live-search';
import Chat from './chat';

// only loads the search feature if the class exists on the current page
if (document.querySelector('.header-search-icon')) {
	new Search();
}

if (document.querySelector('.header-chat-icon')) {
	new Chat();
}
