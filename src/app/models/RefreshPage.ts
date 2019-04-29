import {OnDestroy, OnInit} from '@angular/core';

export abstract class RefreshPage implements OnInit, OnDestroy {

	protected intervalId: number;
	protected halt = false;
	private bizzy = false;

	protected constructor(protected refreshInterval: number) {
	}

	abstract onRefresh();

	public setHalt(halt) {
		this.halt = halt;
	}

	public isHalting() {
		return this.halt;
	}

	private triggerRefresh(ttl = 0) {
		if (this.bizzy) {
			console.error('Job took more time then expected!');
			return;
		}
		if (this.isHalting()) {
			if (ttl > this.refreshInterval) {
				return;
			}
			const ttlAdd = this.refreshInterval / 100;
			setTimeout(() => {
				this.triggerRefresh(ttl + ttlAdd);
			}, this.refreshInterval * 10);
		} else {
			this.bizzy = true;
			this.onRefresh();
			this.bizzy = false;
		}
	}

	ngOnInit(): void {
		// @ts-ignore
		this.intervalId = setInterval(() => {
			this.triggerRefresh();
		}, this.refreshInterval * 1000);
	}

	ngOnDestroy(): void {
		clearInterval(this.intervalId);
	}
}
