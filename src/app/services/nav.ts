export function navItemListener(listener?: (value: any) => void) {
	if (!navItems) {
		listeners.push(listener);
		return;
	}
	listener(navItems);
}

export function setNav(nav) {
	navItems = nav;
	let listener;
	while ((listener = listeners.pop()) !== undefined) {
		listener(navItems);
	}
}

let navItems = null;
const listeners = [];
