<script setup>
import { ref, onMounted, watch, shallowRef, markRaw, nextTick, onBeforeMount } from 'vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import Toast from 'primevue/toast';
import { useToast } from 'primevue/usetoast';
import { Form } from '@primevue/forms';
import moment from 'moment';
import Panel from 'primevue/panel';
import * as echarts from 'echarts';

moment.locale('id')
const toast = useToast();
const datas = defineProps({
    lists: Object,
})

const detailDialog = ref(false)
const dataDevice = ref()
const loading = ref(true)
const submitted = ref(false)
const setHostname = ref()
const detailCard = ref({
    version: '',
    ip: '',
    hostname: '',
    architecture: '',
    os: '',
    ram: '',
    core: '',
    cpu: '',
    virtualization: '',
    uptime: '',
    tz: '',
    alarm: {
        normal: 0,
        warning: 0,
        critical: 0,
    }
})

// chart line
const dataUptime = ref('0')
const chartRef = ref({})
const chartInstance = shallowRef({})
const chartDataStore = ref({
    load: { labels: [], values: [] },
    cpu: { labels: [], values: [] },
    mem_use: { labels: [], values: [] },
    mem_ava: { labels: [], values: [] },
    disk_r: { labels: [], values: [] },
    disk_w: { labels: [], values: [] },
    net_in: { labels: [], values: [] },
    net_out: { labels: [], values: [] },
});

// chart doughnut
const cpuMetrics = ['user', 'system', 'iowait', 'steal', 'softirq', 'irq', 'nice'];
const cpuChartInstances = shallowRef({});
const cpuDataStore = ref({});
cpuMetrics.forEach(metric => {
    cpuDataStore.value[metric] = { labels: [], values: [] };
});

const isDarkMode = () => document.documentElement.classList.contains('p-dark')

// multi-chart option
const createOption = (key, title, labels, values, color, isBytes = false) => ({
    backgroundColor: 'transparent',
    // title: { text: title, textStyle: { fontSize: 13, fontWeight: 'normal' } },
    tooltip: { 
        trigger: 'axis', 
        backgroundColor: isDarkMode() ? '#1e1e1e' : '#fff',
        formatter: (params) => {
            let res = `<div style="font-weight:bold; margin-bottom:5px;">${params[0].name}</div>`;
            params.forEach(item => {
                const formattedValue = isBytes ? formatBytesAuto(item.value) : (item.value.toFixed(2) + `${key === 'cpu' ? '%' : ''}`)
                res += `
                    <div style="display:flex; justify-content:between; gap:10px;">
                        <span>${item.marker} ${item.seriesName}</span>
                        <span style="font-weight:bold;">${formattedValue}</span>
                    </div>
                `;
            });
            return res;
        }
    },
    grid: { left: '3%', right: '4%', bottom: '3%', containLabel: true },
    xAxis: { 
        type: 'category', 
        boundaryGap: false, 
        data: labels,
        axisLabel: { color: isDarkMode() ? '#aaa' : '#666' }
    },
    yAxis: { 
        type: 'value', 
        max: (key === 'cpu' ? 100 : null),
        axisLabel: { 
            formatter: (value) => isBytes ? formatBytesAuto(value, 1) : value,
            color: isDarkMode() ? '#999' : '#666',
            fontSize: 10,
        },
        splitLine: { lineStyle: { type: 'dashed', opacity: 0.1 } } 
    },
    series: [{
        name: title,
        type: 'line',
        smooth: true,
        showSymbol: false,
        data: values,
        lineStyle: { color: color, width: 2 },
        areaStyle: {
            color: new echarts.graphic.LinearGradient(0, 0, 0, 1, [
                { offset: 0, color: color + '66' },
                { offset: 1, color: color + '00' }
            ])
        }
    }],
    animationDurationUpdate: 1000,
    animationEasingUpdate: 'linear'
})

const initCharts = () => {
    const documentStyle = getComputedStyle(document.documentElement);
    const configs = [
        { key: 'load', label: 'CPU Load Avg', color: documentStyle.getPropertyValue('--p-teal-500'), converts: false },
        { key: 'cpu', label: 'CPU Usage', color: documentStyle.getPropertyValue('--p-green-500'), converts: false },
        { key: 'mem_use', label: 'Memory in Use', color: documentStyle.getPropertyValue('--p-yellow-500'), converts: true },
        { key: 'mem_ava', label: 'Memory Available', color: documentStyle.getPropertyValue('--p-red-500'), converts: true },
        { key: 'disk_r', label: 'Disk Read', color: documentStyle.getPropertyValue('--p-purple-500'), converts: true },
        { key: 'disk_w', label: 'Disk Write', color: documentStyle.getPropertyValue('--p-indigo-500'), converts: true },
        { key: 'net_in', label: 'Network In', color: documentStyle.getPropertyValue('--p-lime-500'), converts: true },
        { key: 'net_out', label: 'Network Out', color: documentStyle.getPropertyValue('--p-slate-500'), converts: true },
    ]

    configs.forEach(cfg => {
        const el = chartRef.value[cfg.key]
        if (!el) return

        if (chartInstance.value[cfg.key]) chartInstance.value[cfg.key].dispose()

        const instance = echarts.init(el, isDarkMode() ? 'dark' : null)
        instance.setOption(createOption(cfg.key, cfg.label, chartDataStore.value[cfg.key].labels, chartDataStore.value[cfg.key].values, cfg.color, cfg.converts))
        chartInstance.value[cfg.key] = instance
    })
}

const initData = () => {
    dataDevice.value = Array.isArray(datas.lists) ? [...datas.lists] : []
    loading.value = false
}

const get_detail = (dt) => {
    setHostname.value = dt
    const form = useForm({ host: dt })
    submitted.value = true

    form.post('/monitoring/detail', {
        resetOnSuccess: true,
        onSuccess: (res) => {
            const messages = res.props.flash.message
            if (messages.status === 'success') {
                fetch_detail(messages.summary)
                
                // init data for chart
                parseInitialData('load', messages.data.load)
                parseInitialData('cpu', messages.data.cpu)
                parseInitialData('mem_use', messages.data.memory)
                parseInitialData('mem_ava', messages.data.memory)
                parseInitialData('disk_r', messages.data.disk)
                parseInitialData('disk_w', messages.data.disk)
                parseInitialData('net_in', messages.data.network)
                parseInitialData('net_out', messages.data.network)

                const isUptime = messages.data.uptime
                dataUptime.value = formatUptime(isUptime.data[0][Object.keys(isUptime.labels).find(k => isUptime.labels[k] === "uptime")])

                initCpuDetailCharts()
                updateCpuDetails(messages.data.cpu)

                detailDialog.value = true
            } else {
                toast.add({ severity: 'error', summary: 'Peringatan', detail: messages.message, life: 3000 });
            }

            submitted.value = false
        },
        onError: () => {
            toast.add({ severity: 'error', summary: 'Peringatan', detail: 'Terjadi kesalahan pada sistem', life: 3000 });
            submitted.value = false
        }
    })
}

const parseInitialData = (key, rawData) => {
    const keyTime = Object.keys(rawData.labels).find(k => rawData.labels[k] === "time");
    let keyVal = null

    if (key === 'load') {
        keyVal = Object.keys(rawData.labels).find(k => rawData.labels[k] === "load1");
    } 
    if (key === 'cpu') {
        keyVal = Object.keys(rawData.labels).find(k => rawData.labels[k] === "system");
    }
    if (key === 'mem_use') {
        keyVal = Object.keys(rawData.labels).find(k => rawData.labels[k] === "used");
    }
    if (key === 'mem_ava') {
        keyVal = Object.keys(rawData.labels).find(k => rawData.labels[k] === "free");
    }
    if (key === 'disk_r') {
        keyVal = Object.keys(rawData.labels).find(k => rawData.labels[k] === "reads");
    }
    if (key === 'disk_w') {
        keyVal = Object.keys(rawData.labels).find(k => rawData.labels[k] === "writes");
    }
    if (key === 'net_in') {
        keyVal = Object.keys(rawData.labels).find(k => rawData.labels[k] === "received");
    }
    if (key === 'net_out') {
        keyVal = Object.keys(rawData.labels).find(k => rawData.labels[k] === "sent");
    }
    
    chartDataStore.value[key].labels = rawData.data.slice(0, 15).map(d => moment(d[keyTime]*1000).format('HH:mm:ss')).reverse();
    // chartDataStore.value[key].values = rawData.data.slice(0, 15).map(d => Math.abs(d[keyVal].toFixed(2))).reverse();
    chartDataStore.value[key].values = rawData.data.slice(0, 15).map(d => {
        if (key === 'cpu') {
            const total = Math.abs(d.reduce((accumulator, currentValue) => accumulator + currentValue, 0))
            return total - d[0]
        } else if (key === 'mem_ava') {
            const free  = Math.abs(d[keyVal])
            const cache = Math.abs(d[Object.keys(rawData.labels).find(k => rawData.labels[k] === "cached")])
            const buffer= Math.abs(d[Object.keys(rawData.labels).find(k => rawData.labels[k] === "buffers")])

            return Math.abs(free + cache + buffer) * 1024 * 1024
        } else if (key === 'mem_use') {
            return Math.abs(d[keyVal]) * 1024 * 1024
        } else {
            return Math.abs(d[keyVal])
        }
        // Math.abs(d[keyVal].toFixed(2))
    }).reverse();
}

const get_continues = () => {
    const form = useForm({ host: setHostname.value })
    submitted.value = true

    form.post('/monitoring/detail', {
        resetOnSuccess: true,
        onSuccess: (res) => {
            const messages = res.props.flash.message
            if (messages.status === 'success') {
                fetch_detail(messages.summary)
                cpu_continues('load', messages.data.load)
                cpu_continues('cpu', messages.data.cpu)
                cpu_continues('mem_use', messages.data.memory)
                cpu_continues('mem_ava', messages.data.memory)
                cpu_continues('disk_r', messages.data.disk)
                cpu_continues('disk_w', messages.data.disk)
                cpu_continues('net_in', messages.data.network)
                cpu_continues('net_out', messages.data.network)

                const isUptime = messages.data.uptime
                dataUptime.value = formatUptime(isUptime.data[0][Object.keys(isUptime.labels).find(k => isUptime.labels[k] === "uptime")])

                initCpuDetailCharts()
                updateCpuDetails(messages.data.cpu)
            } else {
                toast.add({ severity: 'error', summary: 'Peringatan', detail: messages.message, life: 3000 });
            }
            submitted.value = false
        },
        onError: () => {
            toast.add({ severity: 'error', summary: 'Peringatan', detail: 'Terjadi kesalahan pada sistem', life: 3000 });
            submitted.value = false
        }
    })
}

const fetch_detail = (data) => {
    if (data) {
        detailCard.value.version    = data.version
        detailCard.value.ip         = data.host_labels._net_default_iface_ip
        detailCard.value.hostname   = data.host_labels._hostname
        detailCard.value.architecture = data.host_labels._architecture
        detailCard.value.os         = data.os_name +'/'+ data.os_version
        detailCard.value.ram        = formatBytes(data.ram_total)
        detailCard.value.core       = data.cores_total
        detailCard.value.cpu        = data.host_labels._system_cpu_model
        detailCard.value.virtualization = data.virtualization
        detailCard.value.uptime     = dataUptime.value
        detailCard.value.tz         = data.host_labels._timezone
        detailCard.value.alarm.normal   = data.alarms.normal
        detailCard.value.alarm.warning  = data.alarms.warning
        detailCard.value.alarm.critical = data.alarms.critical

    }
}

const formatBytes = (bytes) => {
    const decimals = 2
    if (!+bytes) return '0 Bytes';

    const k = 1024;
    const dm = decimals < 0 ? 0 : decimals;
    const sizes = ['Bytes', 'KiB', 'MiB', 'GiB', 'TiB', 'PiB'];

    // Find the exponent (0 for Bytes, 1 for KB, etc.)
    const i = Math.floor(Math.log(bytes) / Math.log(k));

    return `${parseFloat((bytes / Math.pow(k, i)).toFixed(dm))} ${sizes[i]}`;
}

const formatBytesAuto = (bytes, decimals = 2) => {
    if (!+bytes) return '0 B';
    const k = 1024;
    const dm = decimals < 0 ? 0 : decimals;
    const sizes = ['B', 'KB', 'MB', 'GB', 'TB', 'PB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    // return `${parseFloat((bytes / Math.pow(k, i)).toFixed(dm))} ${sizes[i]}`;
    const index = Math.min(i, sizes.length - 1);
    return (bytes / Math.pow(k, index)).toFixed(dm) + ' ' + sizes[index];
}

const formatUptime = (seconds) => {
    const s = Math.floor(seconds);
    const d = Math.floor(s / 86400);
    const h = Math.floor((s % 86400) / 3600);
    const m = Math.floor((s % 3600) / 60);
    const sec = s % 60;

    const parts = [];
    if (d > 0) parts.push(`${d}d`);
    if (h > 0) parts.push(`${h}h`);
    if (m > 0) parts.push(`${m}m`);
    if (parts.length === 0 || sec > 0) parts.push(`${sec}s`);

    return parts.join(' ');
};

const cpu_continues = (key, datas) => {
    const keyTime = Object.keys(datas.labels).find(key => datas.labels[key] === "time")
    // const keyCpu  = Object.keys(datas.labels).find(key => datas.labels[key] === "system")
    let keyVal = null

    if (key === 'load') {
        keyVal = Object.keys(datas.labels).find(k => datas.labels[k] === "load1");
    }
    if (key === 'cpu') {
        keyVal = Object.keys(datas.labels).find(k => datas.labels[k] === "system");
    }
    if (key === 'mem_use') {
        keyVal = Object.keys(datas.labels).find(k => datas.labels[k] === "used");
    }
    if (key === 'mem_ava') {
        keyVal = Object.keys(datas.labels).find(k => datas.labels[k] === "free");
    }
    if (key === 'disk_r') {
        keyVal = Object.keys(datas.labels).find(k => datas.labels[k] === "reads");
    }
    if (key === 'disk_w') {
        keyVal = Object.keys(datas.labels).find(k => datas.labels[k] === "writes");
    }
    if (key === 'net_in') {
        keyVal = Object.keys(datas.labels).find(k => datas.labels[k] === "received");
    }
    if (key === 'net_out') {
        keyVal = Object.keys(datas.labels).find(k => datas.labels[k] === "sent");
    }

    const data = datas.data
    if (data && data.length > 0) {
        // console.log('interval chart')
        const setDate = moment(new Date(datas.data[0][keyTime] * 1000)).format('HH:mm:ss')
        let setValue  = null
        if (key === 'cpu') {
            const total = Math.abs(datas.data[0].reduce((accumulator, currentValue) => accumulator + currentValue, 0))
            setValue = total - datas.data[0][0]
        } else if (key === 'mem_ava') {
            const free  = Math.abs(datas.data[0][keyVal])
            const cache = Math.abs(datas.data[0][Object.keys(datas.labels).find(k => datas.labels[k] === "cached")])
            const buffer= Math.abs(datas.data[0][Object.keys(datas.labels).find(k => datas.labels[k] === "buffers")])

            setValue = Math.abs(free + cache + buffer) * 1024 * 1024
        } else if (key === 'mem_use') {
            setValue = Math.abs(datas.data[0][keyVal]) * 1024 *1024
        } else {
            setValue = Math.abs(datas.data[0][keyVal])
        }

        const store = chartDataStore.value[key]
        store.labels.push(setDate)
        store.values.push(setValue)

        if (store.labels.length > 20) {
            store.labels.shift()
            store.values.shift()
        }

        chartInstance.value[key]?.setOption({
            xAxis: { data: store.labels },
            series: [{ data: store.values }]
        })
    }
}

const alert_response = (rsp) => {
    if (rsp.status === 'failed') {
        toast.add({ severity: 'error', summary: 'Error', detail: rsp.msg, life: 3000 });
    } else if (rsp.status === 'success') {
        toast.add({ severity: 'success', summary: 'Berhasil', detail: rsp.msg, life: 3000 });
    }
}

let timer = null
watch(detailDialog, async(isOpen) => {
    if (isOpen) {
        await nextTick()
        initCharts()
        timer = setInterval(() => { get_continues() }, 3000)
        window.addEventListener('resize', handleResize)
        console.log('started')
    } else {
        clearInterval(timer)
        timer = null
        window.addEventListener('resize', handleResize)
        Object.values(chartInstance.value).forEach(i => i.dispose())
        chartInstance.value = {}
        console.log('stopped')
    }
})

const handleResize = () => Object.values(chartInstance.value).forEach(i => i.resize());

const initCpuDetailCharts = () => {
    cpuMetrics.forEach(metric => {
        const el = document.getElementById(`chart-cpu-${metric}`);
        if (!el) return;

        const instance = echarts.init(el, isDarkMode() ? 'dark' : null);
        
        instance.setOption({
            title: { 
                text: metric.toUpperCase(), 
                left: 'center', 
                textStyle: { fontSize: 12, fontWeight: 'bold' } 
            },
            grid: { top: 30, bottom: 20, left: 30, right: 10 },
            xAxis: { type: 'category', data: [], axisLabel: { show: false } },
            yAxis: { type: 'value', max: 100, splitLine: { lineStyle: { opacity: 0.1 } } },
            series: [{
                name: metric,
                type: 'pie',
                radius: ['40%', '70%'],
                smooth: true,
                showSymbol: false,
                areaStyle: { opacity: 0.2 },
                data: [],
                itemStyle: { 
                    color: getMetricColor(metric),
                    borderRadius: 8,
                    borderColor: '#fff',
                    borderWidth: 2
                }
            }]
        });
        cpuChartInstances.value[metric] = instance;
    });
};

// Helper for distinct colors
const getMetricColor = (m) => {
    const colors = {
        user: '#3b82f6',    // Blue
        system: '#ef4444',  // Red
        iowait: '#f59e0b',  // Orange
        steal: '#8b5cf6',   // Purple
        softirq: '#ec4899', // Pink
        irq: '#06b6d4',     // Cyan
        nice: '#10b981'     // Green
    };
    return colors[m] || '#64748b';
};

const updateCpuDetails = (rawData) => {
    const labels = rawData.labels;
    const dataRow = rawData.data[0];
    const timeIndex = labels.indexOf('time');
    const timeLabel = moment(dataRow[timeIndex] * 1000).format('HH:mm:ss');

    cpuMetrics.forEach(metric => {
        const valIndex = labels.indexOf(metric);
        if (valIndex === -1) return;

        const val = dataRow[valIndex];
        const store = cpuDataStore.value[metric];

        store.labels = [timeLabel]
        store.values = [val.toFixed(2)]

        // if (store.labels.length > 20) {
        //     store.labels.shift();
        //     store.values.shift();
        // }

        cpuChartInstances.value[metric]?.setOption({
            xAxis: { data: store.labels },
            // series: [{ data: store.values }]
            series: [
                {
                    data: [
                        {
                            value: store.values,
                        },
                        {
                            value: (100 - store.values)
                        }
                    ]
                }
            ]
        });
    });
};

onMounted(() => {
    initData()
})
</script>

<template>
    <Toast />

    <Card class="w-full">
        <template #title><i class="pi pi-server"></i> Data Device</template>
        <template #content>
            <div class="mt-5">
                <DataTable :value="dataDevice" paginator showGridlines :rows="15" :rowsPerPageOptions="[5, 10, 15, 20, 50]" tableStyle="min-width: 50rem" :loading="loading">
                    <template #header>
                        <div class="flex justify-between">
                            <div class="flex flex-wrap gap-4">
                                <Button type="button" severity="info" icon="pi pi-plus" label="Tambah Data" @click="newData()" />
                            </div>
                        </div>
                    </template>
                    <template #empty><div class="w-full text-center">Tidak ada data.</div></template>
                    <template #loading><div class="w-full text-center">Memproses data. Harap menunggu.</div> </template>

                    <Column field="" header="#" style="width: 5%">
                        <template #body="{ slotProps, index }">
                            <label>{{ index + 1 }}</label>
                        </template>
                    </Column>
                    <Column field="hostname" header="Nama Device / Server" style="width: 30%">
                        <template #body="{ data }">
                            {{ data.hostname }}
                        </template>
                    </Column>
                    <Column field="hops" header="Hops" style="width: 10%">
                        <template #body="{ data }">
                            {{ data.hops }}
                        </template>
                    </Column>
                    <Column field="" header="Status" style="width: 15%">
                        <template #body="slotProps">
                            <Tag :severity="slotProps.data.reachable ? 'success' : 'danger'" :value="slotProps.data.reachable ? 'Online' : 'Offline'"></Tag>
                        </template>
                    </Column>
                    <Column field="guid" header="GUID" style="width: 30%" />

                    <Column header="Opsi" style="width: 10%; text-align: right;" class="justify-items-center">
                        <template #body="slotProps">
                            <div class="w-full text-center">
                                <Button icon="pi pi-chart-bar" @click="get_detail(slotProps.data.hostname)" v-tooltip.bottom="'Lihat Detail'" :severity="slotProps.data.reachable ? 'success' : 'danger'" variant="text" raised rounded :disabled="!slotProps.data.reachable" />
                            </div>
                        </template>
                    </Column>

                    <template #paginatorcontainer="{ first, last, page, pageCount, prevPageCallback, nextPageCallback, totalRecords }">
                        <div class="flex items-center gap-4 border border-primary bg-transparent rounded-full w-full py-1 px-2 justify-between">
                            <Button icon="pi pi-chevron-left" rounded text @click="prevPageCallback" :disabled="page === 0" />
                            <div class="text-color font-medium">
                                <span class="hidden sm:block">Showing {{ first }} to {{ last }} of {{ totalRecords }}</span>
                                <span class="block sm:hidden">Page {{ page + 1 }} of {{ pageCount }}</span>
                            </div>
                            <Button icon="pi pi-chevron-right" rounded text @click="nextPageCallback" :disabled="page === pageCount - 1" />
                        </div>
                    </template>
                </DataTable>
            </div>
        </template>
    </Card>

    <Dialog v-model:visible="detailDialog" maximizable modal header="Detail Device" :style="{width: '90rem'}" :breakpoints="{ '1199px': '75vw', '575px': '90vw' }">
        <Panel header="Server Summary" class="mb-5" toggleable>
            <div class="grid grid-cols-12 gap-8">
                <div class="col-span-6 lg:col-span-6 xl:col-span-3">
                    <div class="card mb-0 border h-full">
                        <div class="flex justify-between mb-4">
                            <div>
                                <span class="block text-muted-color font-medium mb-4">Netdata Version</span>
                                <div class="text-surface-900 dark:text-surface-0 font-medium text-xl">
                                    <i class="pi pi-tag text-blue-500 !text-xl"></i> &nbsp;
                                    {{ detailCard.version }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-span-6 lg:col-span-6 xl:col-span-3">
                    <div class="card mb-0 border h-full">
                        <div class="flex justify-between mb-4">
                            <div>
                                <span class="block text-muted-color font-medium mb-4">Server IP</span>
                                <div class="text-surface-900 dark:text-surface-0 font-medium text-xl">
                                    <i class="pi pi-globe text-blue-500 !text-xl"></i> &nbsp;
                                    {{ detailCard.ip }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-span-6 lg:col-span-6 xl:col-span-3">
                    <div class="card mb-0 border h-full">
                        <div class="flex justify-between mb-4">
                            <div>
                                <span class="block text-muted-color font-medium mb-4">Server Hostname</span>
                                <div class="text-surface-900 dark:text-surface-0 font-medium text-xl">
                                    <i class="pi pi-server text-blue-500 !text-xl"></i> &nbsp;
                                    {{ detailCard.hostname }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-span-6 lg:col-span-6 xl:col-span-3">
                    <div class="card mb-0 border h-full">
                        <div class="flex justify-between mb-4">
                            <div>
                                <span class="block text-muted-color font-medium mb-4">Architecture</span>
                                <div class="text-surface-900 dark:text-surface-0 font-medium text-xl">
                                    <i class="pi pi-microchip text-blue-500 !text-xl"></i> &nbsp;
                                    {{ detailCard.architecture }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-span-6 lg:col-span-6 xl:col-span-3">
                    <div class="card mb-0 border h-full">
                        <div class="flex justify-between mb-4">
                            <div>
                                <span class="block text-muted-color font-medium mb-4">Operating System</span>
                                <div class="text-surface-900 dark:text-surface-0 font-medium text-xl">
                                    <i class="pi pi-desktop text-blue-500 !text-xl"></i> &nbsp;
                                    {{ detailCard.os }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-span-6 lg:col-span-6 xl:col-span-3">
                    <div class="card mb-0 border h-full">
                        <div class="flex justify-between mb-4">
                            <div>
                                <span class="block text-muted-color font-medium mb-4">Total RAM</span>
                                <div class="text-surface-900 dark:text-surface-0 font-medium text-xl">
                                    <i class="pi pi-database text-blue-500 !text-xl"></i> &nbsp;
                                    {{ detailCard.ram }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-span-6 lg:col-span-6 xl:col-span-3">
                    <div class="card mb-0 border h-full">
                        <div class="flex justify-between mb-4">
                            <div>
                                <span class="block text-muted-color font-medium mb-4">CPU Cores</span>
                                <div class="text-surface-900 dark:text-surface-0 font-medium text-xl">
                                    <i class="pi pi-microchip text-blue-500 !text-xl"></i> &nbsp;
                                    {{ detailCard.core }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-span-6 lg:col-span-6 xl:col-span-3">
                    <div class="card mb-0 border h-full">
                        <div class="flex justify-between mb-4">
                            <div>
                                <span class="block text-muted-color font-medium mb-4">CPU Type</span>
                                <div class="text-surface-900 dark:text-surface-0 font-medium text-xl">
                                    <i class="pi pi-microchip text-blue-500 !text-xl"></i> &nbsp;
                                    {{ detailCard.cpu }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-span-6 lg:col-span-6 xl:col-span-3">
                    <div class="card mb-0 border h-full">
                        <div class="flex justify-between mb-4">
                            <div>
                                <span class="block text-muted-color font-medium mb-4">Virtualization</span>
                                <div class="text-surface-900 dark:text-surface-0 font-medium text-xl">
                                    <i class="pi pi-clone text-blue-500 !text-xl"></i> &nbsp;
                                    {{ detailCard.virtualization }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-span-6 lg:col-span-6 xl:col-span-3">
                    <div class="card mb-0 border h-full">
                        <div class="flex justify-between mb-4">
                            <div>
                                <span class="block text-muted-color font-medium mb-4">Uptime</span>
                                <div class="text-surface-900 dark:text-surface-0 font-medium text-xl">
                                    <i class="pi pi-stopwatch text-blue-500 !text-xl"></i> &nbsp;
                                    {{ detailCard.uptime }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-span-6 lg:col-span-6 xl:col-span-3">
                    <div class="card mb-0 border h-full">
                        <div class="flex justify-between mb-4">
                            <div>
                                <span class="block text-muted-color font-medium mb-4">Timezone</span>
                                <div class="text-surface-900 dark:text-surface-0 font-medium text-xl">
                                    <i class="pi pi-camera text-blue-500 !text-xl"></i> &nbsp;
                                    {{ detailCard.tz }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-span-6 lg:col-span-6 xl:col-span-3">
                    <div class="card mb-0 border h-full">
                        <div class="flex justify-between mb-4">
                            <div>
                                <span class="block text-muted-color font-medium mb-4">Alarm</span>
                                <div class="text-surface-900 dark:text-surface-0 font-medium text-xl">
                                    <i class="pi pi-bell text-green-500 !text-xl" v-tooltip="'Normal'"></i>
                                    {{ detailCard.alarm.normal }} &nbsp;&nbsp;

                                    <i class="pi pi-bell text-yellow-500 !text-xl" v-tooltip="'Warning'"></i>
                                    {{ detailCard.alarm.warning }} &nbsp;&nbsp;

                                    <i class="pi pi-bell text-red-500 !text-xl" v-tooltip="'Critical'"></i>
                                    {{ detailCard.alarm.critical }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </Panel>

        <Panel header="Real-time Performance" class="mb-5" toggleable>
            <div class="grid grid-cols-12 gap-8">
                <div class="col-span-12 lg:col-span-6 xl:col-span-6">
                    <div class="card mb-0 h-full">
                         <h5 class="text-center">CPU Load Average</h5>
                         <div :ref="el => chartRef['load'] = el" class="h-[20rem]"></div>
                    </div>
                </div>
                <div class="col-span-12 lg:col-span-6 xl:col-span-6">
                    <div class="card mb-0 h-full">
                         <h5 class="text-center">CPU Usage %</h5>
                         <div :ref="el => chartRef['cpu'] = el" class="h-[20rem]"></div>
                    </div>
                </div>
                <div class="col-span-12 lg:col-span-6 xl:col-span-6">
                    <div class="card mb-0 h-full">
                         <h5 class="text-center">Memory in Use</h5>
                         <div :ref="el => chartRef['mem_use'] = el" class="h-[20rem]"></div>
                    </div>
                </div>
                <div class="col-span-12 lg:col-span-6 xl:col-span-6">
                    <div class="card mb-0 h-full">
                         <h5 class="text-center">Memory Available</h5>
                         <div :ref="el => chartRef['mem_ava'] = el" class="h-[20rem]"></div>
                    </div>
                </div>
                <div class="col-span-12 lg:col-span-6 xl:col-span-6">
                    <div class="card mb-0 h-full">
                         <h5 class="text-center">Disk Read (Bytes)</h5>
                         <div :ref="el => chartRef['disk_r'] = el" class="h-[20rem]"></div>
                    </div>
                </div>
                <div class="col-span-12 lg:col-span-6 xl:col-span-6">
                    <div class="card mb-0 h-full">
                         <h5 class="text-center">Disk Write (Bytes)</h5>
                         <div :ref="el => chartRef['disk_w'] = el" class="h-[20rem]"></div>
                    </div>
                </div>
                <div class="col-span-12 lg:col-span-6 xl:col-span-6">
                    <div class="card mb-0 h-full">
                         <h5 class="text-center">Network In (Bytes)</h5>
                         <div :ref="el => chartRef['net_in'] = el" class="h-[20rem]"></div>
                    </div>
                </div>
                <div class="col-span-12 lg:col-span-6 xl:col-span-6">
                    <div class="card mb-0 h-full">
                         <h5 class="text-center">Network Out (Bytes)</h5>
                         <div :ref="el => chartRef['net_out'] = el" class="h-[20rem]"></div>
                    </div>
                </div>
            </div>
        </Panel>

        <Panel header="Memory Details" class="mb-5" toggleable="">
            <div class="grid grid-cols-12 gap-8">
                <div v-for="metric in cpuMetrics" :key="metric" class="col-span-12 md:col-span-4 lg:col-span-3 border p-2 rounded bg-white dark:bg-gray-800">
                    
                    <div :id="`chart-cpu-${metric}`" style="height: 150px; width: 100%;"></div>
                    
                    <div class="text-center text-xs text-gray-500 mt-1">
                        Current: <span class="font-mono font-bold text-gray-800 dark:text-gray-200">
                            {{ cpuDataStore[metric].values.slice(-1)[0] || 0 }}%
                        </span>
                    </div>
                </div>
            </div>
        </Panel>

        <div class="flex flex-col md:flex-row mt-20">
            <div class="w-full flex flex-col gap-4">
                <div class="card flex flex-wrap gap-4 -mt-20 justify-items-center">
                    <div class="flex-auto text-center">
                        <Button type="button" label="Tutup" severity="danger" class="flex md:w-6/12 sm:w-12/12 btn-block" icon="pi pi-times" raised @click="detailDialog = false" />
                    </div>
                </div>
            </div>
        </div>
    </Dialog>
</template>