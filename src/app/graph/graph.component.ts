import {Component, HostListener, OnInit, ViewChild} from '@angular/core';
import {HttpClient, HttpHeaders} from '@angular/common/http';
import {ActivatedRoute} from '@angular/router';
import {ChartDataSets, ChartOptions} from 'chart.js';
import {AppComponent} from '../app.component';
import {GLOBAL} from '../GLOBAL';
import {Label} from 'ng2-charts';
import * as pluginAnnotations from 'chartjs-plugin-annotation';
import {DomSanitizer} from '@angular/platform-browser';

@Component({
    selector: 'app-graph',
    templateUrl: './graph.component.html',
    styleUrls: ['./graph.component.scss']
})
export class GraphComponent implements OnInit {

    constructor(private http: HttpClient,
                private route: ActivatedRoute,
                public sanitizer: DomSanitizer) {
    }

    private static oneDayInMillis = 86400000;

    public loggedIn: string = localStorage.getItem(AppComponent.loggedInKey);

    public loading = true;
    public queueIndex;
    public lineChartData: ChartDataSets[];
    public lineChartDataClosed: ChartDataSets[];
    public lineChartLabels: Label[];
    public lineChartPlugins = [pluginAnnotations];
    public lineChartOptions: (ChartOptions & { annotation?: any, plugins?: any }) = {
        responsive: true,
        aspectRatio: 3,
        scales: {
            yAxes: [{
                ticks: {
                    beginAtZero: true
                }
            }]
        },
        annotation: {
            annotations: [{
                type: 'line',
                mode: 'vertical',
                scaleID: 'x-axis-0',
                value: AppComponent.formatDate(),
                borderColor: 'orange',
                borderWidth: 2,
                label: {
                    enabled: true,
                    fontColor: 'orange',
                    content: 'Today'
                }
            }],
        },
        plugins: {
            datalabels: {
                color: 'rgba(0,0,0,1)',
                formatter(value: number, ctx: any) {
                    const dataMinusLast = ctx.dataset.data.slice(1, -1);
                    const min = Math.min(...dataMinusLast);
                    const max = Math.max(...dataMinusLast);
                    const indexF = dataMinusLast.indexOf(max) + 1;
                    const indexL = dataMinusLast.lastIndexOf(min) + 1;
                    if ((ctx.dataIndex !== indexF && ctx.dataIndex !== indexL) ||
                        ctx.dataIndex === 0 || ctx.dataIndex === ctx.dataset.data.length - 1) {
                        return '';
                    }
                    return value === 0 ? '' : value;
                }
            }
        }
    };
    @ViewChild('graph') graph;


    public ticketIds = [];
    public ticketQueue = {};
    public minDate;
    public maxDate;
    public scopeDate;

    private static loopThroughDates(call: (date: string) => void, startDate = '2000-01-01', endDate: null | Date = null) {
        let lastDate = startDate;
        let lastDateObj = new Date(lastDate);
        let today;
        if (endDate == null) {
            today = AppComponent.formatDate();
        } else {
            today = AppComponent.formatDate(endDate);
        }
        let iteration = 10000;
        call(lastDate);
        while (lastDate < today && (iteration-- > 0)) {
            lastDateObj = new Date(new Date(lastDate).getTime() + this.oneDayInMillis);
            lastDate = AppComponent.formatDate(lastDateObj);

            call(lastDate);
        }
    }

    ngOnInit() {
        this.route.queryParams.subscribe(param => {
            this.resetData();
            if (param.min) {
                this.minDate = param.min;
                this.scopeDate = param.min;
            } else {
                this.scopeDate = null;
            }
            if (param.max) {
                this.maxDate = new Date(param.max);
            }
            this.loadQueues();
        });
    }

    resetData() {
        this.minDate = AppComponent.formatDate();
        this.maxDate = new Date(new Date(AppComponent.formatDate(null, true, -1)).getTime() - GraphComponent.oneDayInMillis);
        this.scopeDate = AppComponent.formatDate(new Date(), true);
        this.queueIndex = [];
        this.lineChartData = [];
        this.lineChartDataClosed = [];
        this.lineChartLabels = [];
        this.ticketIds = [];
        this.ticketQueue = {};
        this.loading = true;
    }

    loadQueues() {
        const headers = new HttpHeaders().set('x-dell-api-version', '1').set('x-dell-csrf-token', this.loggedIn);

        this.http.get(GLOBAL.kaceURL + 'api/service_desk/queues/', {headers, withCredentials: true}).subscribe((response: any) => {
            response.Queues.forEach(queue => {
                this.queueIndex.push(queue.id);
                this.lineChartData.push({
                    data: [],
                    label: queue.name
                });
                this.lineChartDataClosed.push({
                    data: [],
                    label: queue.name
                });
            });
            this.lineChartData.push({data: [], label: 'All'});
            this.lineChartDataClosed.push({data: [], label: 'All'});
            this.loadTickets(headers);
        });
    }

    loadTickets(headers) {
        const scopeOverride = this.scopeDate == null;
        this.http.get(GLOBAL.kaceURL + 'api/service_desk/tickets/' +
            '?paging=limit ALL', {headers, withCredentials: true}).subscribe((response: any) => {
            response.Tickets.forEach(ticket => {
                this.ticketIds.push(ticket.id);
                this.ticketQueue[ticket.id] = ticket.hd_queue_id;
                const ticketDate = ticket.created.split(' ')[0];
                if (ticketDate < this.minDate) {
                    if (scopeOverride) {
                        // TODO: ticketDate -1dag
                    }
                    this.minDate = ticketDate;
                    if (scopeOverride) {
                        this.scopeDate = ticketDate;
                    }
                }
            });
            this.prepareData();
            this.getTicketHistory(headers);
        });
    }

    prepareData() {
        let max = null;
        if (this.maxDate < new Date()) {
            max = this.maxDate;
        }
        GraphComponent.loopThroughDates(date => {
            if (date >= this.scopeDate) {
                this.lineChartLabels.push(date);
                this.lineChartData.forEach(dataSet => {
                    (dataSet.data as number[]).push(0);
                });
                this.lineChartDataClosed.forEach(dataSet => {
                    (dataSet.data as number[]).push(0);
                });
            }
        }, this.minDate, max);
        if (max == null) {
            const tomorrow = new Date(new Date().getTime() + GraphComponent.oneDayInMillis);
            GraphComponent.loopThroughDates(date => {
                if (date >= this.scopeDate) {
                    this.lineChartLabels.push(date);
                }
            }, AppComponent.formatDate(tomorrow), this.maxDate);
        }
    }

    async getTicketHistory(headers) {
        // @ts-ignore
        window.ticketDebug = {};
        for (const id of this.ticketIds) {
            const ticketHistory: any =
                await this.http.get(GLOBAL.kaceURL + 'api/service_desk/tickets/' + id + '/changes?shaping=status all', {
                    headers,
                    withCredentials: true
                }).toPromise();
            const history = ticketHistory.Changes;
            const regexStatus = /("\w+") to ("\w+")\./;

            const createDate = history[0].timestamp.split(' ')[0];
            let closeDate = null;
            let queueIndex = -1;
            // @ts-ignore
            window.ticketDebug[id] = history;
            history.forEach(change => {
                if (change.description.indexOf('Changed ticket Status') >= 0) {
                    const regexResult = regexStatus.exec(change.description);
                    if (regexResult[2] === '"Closed"' || regexResult[2] === '"Opgelost"') {
                        closeDate = change.timestamp.split(' ')[0];

                        const index = this.lineChartLabels.indexOf(closeDate);

                        (this.lineChartDataClosed[queueIndex].data as number[])[index]++;
                        (this.lineChartDataClosed[this.lineChartDataClosed.length - 1].data as number[])[index]++;
                    } else {
                        closeDate = null;
                    }
                }
                queueIndex = this.queueIndex.indexOf(this.ticketQueue[change.hd_ticket_id]);
            });

            closeDate = closeDate ? new Date(new Date(closeDate).getTime() - GraphComponent.oneDayInMillis) : null;

            GraphComponent.loopThroughDates(date => {
                const index = this.lineChartLabels.indexOf(date);

                (this.lineChartData[queueIndex].data as number[])[index]++;
                (this.lineChartData[this.lineChartData.length - 1].data as number[])[index]++;
            }, createDate, closeDate);
        }
        this.loading = false;
    }

    @HostListener('window:beforeprint', ['$event'])
    onBeforePrint(event) {
        this.graph.nativeElement.children.forEach(div => {
            const img = div.querySelector('img');
            const canvas = div.querySelector('canvas');
            img.src = canvas.toDataURL();
            img.classList.remove('hidden');
            canvas.classList.add('hidden');
        });
        document.querySelectorAll('col').forEach(col => {
            col.style.width = '75px';
        });
    }

    @HostListener('window:afterprint', ['$event'])
    onAfterPrint() {
        this.graph.nativeElement.children.forEach(div => {
            const img = div.querySelector('img');
            const canvas = div.querySelector('canvas');
            img.src = canvas.toDataURL();
            img.classList.add('hidden');
            canvas.classList.remove('hidden');
        });
        document.querySelectorAll('col').forEach(col => {
            col.style.width = '';
        });
    }
}
