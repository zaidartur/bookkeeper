<script setup>
import { ref, onMounted, watch, shallowRef, markRaw, nextTick, onBeforeMount, onUnmounted } from 'vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import Toast from 'primevue/toast';
import { useToast } from 'primevue/usetoast';
import { Form } from '@primevue/forms';
import { FilterMatchMode } from '@primevue/core/api';
import moment from 'moment';
import Panel from 'primevue/panel';
import * as echarts from 'echarts';
import IconField from 'primevue/iconfield';
import InputIcon from 'primevue/inputicon';

moment.locale('id')
const toast = useToast();
const datas = defineProps({
    lists: Object,
})

const filters = ref()
const detailDialog = ref(false)
const dataDevice = ref()
const loading = ref(true)
const submitted = ref(false)
const setHostname = ref()
const setHostType = ref()
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
const initFilters = () => {
    filters.value = {
        global: { value: null, matchMode: FilterMatchMode.CONTAINS },
    };
}

// router & charts
const setRamRouter = ref(0)
const selectedPort = ref()
const dataEther = ref()
const firstEther = ref(0)
const activePort = ref()
const totalDrops = ref(0)
const routerRef = ref({})
const portUp = ref(0)
const portDown = ref(0)
const routerInstance = shallowRef({})
const routerDataStore = ref({
    cpu: { labels: [], values: [], units: '' },
    temp: { labels: [], values: [], units: '' },
    ram_used: { labels: [], values: [], units: '' },
    ram_total: { labels: [], values: [], units: '' },
    disk_used: { labels: [], values: [], units: '' },
    disk_total: { labels: [], values: [], units: '' },
    port: { labels: [], values: [], units: '' },
})

// chart line
const dataUptime = ref('0')
const chartRef = ref({})
const chartInstance = shallowRef({})
const chartDataStore = ref({
    load: { labels: [], values: [], units: '' },
    cpu: { labels: [], values: [], units: '' },
    mem_use: { labels: [], values: [], units: '' },
    mem_ava: { labels: [], values: [], units: '' },
    disk_r: { labels: [], values: [], units: '' },
    disk_w: { labels: [], values: [], units: '' },
    net_in: { labels: [], values: [], units: '' },
    net_out: { labels: [], values: [], units: '' },
});

// chart gauge
const cpuMetrics = ['user', 'system', 'iowait', 'steal', 'softirq', 'irq', 'nice', 'guest', 'guest_nice'];
const ramMetrics = ['free', 'used', 'cached', 'buffers', 'available', 'total'];
const cpuChartInstances = shallowRef({});
const cpuDataStore = ref({});
cpuMetrics.forEach(metric => {
    cpuDataStore.value[metric] = { labels: [], values: [] };
});

const isDarkMode = () => document.documentElement.classList.contains('p-dark')

// multi-chart option
const createOption = (key, title, labels, values, color, isUnit = '') => ({
    backgroundColor: 'transparent',
    // title: { text: title, textStyle: { fontSize: 13, fontWeight: 'normal' } },
    tooltip: { 
        trigger: 'axis', 
        backgroundColor: isDarkMode() ? '#1e1e1e' : '#fff',
        formatter: (params) => {
            let res = `<div style="font-weight:bold; margin-bottom:5px;">${params[0].name}</div>`;
            params.forEach(item => {
                // const formattedValue = isBytes ? formatBytesAuto(item.value) : (item.value.toFixed(2) + `${key === 'cpu' ? '%' : ''}`)
                const formattedValue = `${item.value > 0 ?item.value.toFixed(2) : item.value} ${isUnit}`
                res += `
                    <div style="display:flex; justify-content:between; gap:10px;">
                        <span>${item.marker} ${item.seriesName}</span>
                        <span style="font-weight:bold;">${formattedValue}</span>
                    </div>
                `;
            });
            return res
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
        max: ((key === 'cpu' || key === 'temp') ? 100 : null),
        axisLabel: { 
            formatter: (value) => `${value} ${isUnit}`,
            // formatter: (params) => {
            //     let res = isBytes ? formatBytesAuto(params.value, 1) : params.value
            //     if (isSpeed) {
            //         res += `/sec`
            //     }
            //     return res
            // },
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

const createRouterNetworkOption = (key, title, labels, values, color, isUnit = '') => ({
    backgroundColor: 'transparent',
    // title: { text: title, textStyle: { fontSize: 13, fontWeight: 'normal' } },
    tooltip: { 
        trigger: 'axis', 
        backgroundColor: isDarkMode() ? '#1e1e1e' : '#fff',
        formatter: (params) => {
            let res = `<div style="font-weight:bold; margin-bottom:5px;">${params[0].name}</div>`;
            params.forEach(item => {
                // const formattedValue = isBytes ? formatBytesAuto(item.value) : (item.value.toFixed(2) + `${key === 'cpu' ? '%' : ''}`)
                const formattedValue = `${item.value > 0 ?item.value.toFixed(2) : item.value} ${isUnit}`
                res += `
                    <div style="display:flex; justify-content:between; gap:10px;">
                        <span>${item.marker} ${item.seriesName}</span>
                        <span style="font-weight:bold;">${formattedValue}</span>
                    </div>
                `;
            });
            return res
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
            formatter: (value) => `${value} ${isUnit}`,
            // formatter: (params) => {
            //     let res = isBytes ? formatBytesAuto(params.value, 1) : params.value
            //     if (isSpeed) {
            //         res += `/sec`
            //     }
            //     return res
            // },
            color: isDarkMode() ? '#999' : '#666',
            fontSize: 10,
        },
        splitLine: { lineStyle: { type: 'dashed', opacity: 0.1 } } 
    },
    series: [
        {
            name: 'Upload',
            type: 'line',
            smooth: true,
            showSymbol: false,
            data: values[0],
            lineStyle: { color: '#dc2626', width: 2 },
            itemStyle: { color: '#dc2626', borderWidth: 2 },
            // areaStyle: {
            //     color: new echarts.graphic.LinearGradient(0, 0, 0, 1, [
            //         { offset: 0, color: color + '66' },
            //         { offset: 1, color: color + '00' }
            //     ])
            // }
        },
        {
            name: 'Download',
            type: 'line',
            smooth: true,
            showSymbol: false,
            data: values[1],
            lineStyle: { color: '#0891b2', width: 2 },
            itemStyle: { color: '#0891b2', borderWidth: 2 },
            // areaStyle: {
            //     color: new echarts.graphic.LinearGradient(0, 0, 0, 1, [
            //         { offset: 0, color: color + '66' },
            //         { offset: 1, color: color + '00' }
            //     ])
            // }
        },
    ],
    animationDurationUpdate: 1000,
    animationEasingUpdate: 'linear'
})

const initCharts = () => {
    const documentStyle = getComputedStyle(document.documentElement);
    const configs = [
        { key: 'load', label: 'CPU Load Avg (1 minute)', color: documentStyle.getPropertyValue('--p-teal-500'), units: '' },
        { key: 'cpu', label: 'CPU Usage', color: documentStyle.getPropertyValue('--p-green-500'), units: '' },
        { key: 'mem_use', label: 'Memory in Use', color: documentStyle.getPropertyValue('--p-yellow-500'), units: '' },
        { key: 'mem_ava', label: 'Memory Available', color: documentStyle.getPropertyValue('--p-red-500'), units: '' },
        { key: 'disk_r', label: 'Disk Read', color: documentStyle.getPropertyValue('--p-purple-500'), units: '' },
        { key: 'disk_w', label: 'Disk Write', color: documentStyle.getPropertyValue('--p-indigo-500'), units: '' },
        { key: 'net_in', label: 'Network In', color: documentStyle.getPropertyValue('--p-lime-500'), units: '' },
        { key: 'net_out', label: 'Network Out', color: documentStyle.getPropertyValue('--p-slate-500'), units: '' },
    ]

    configs.forEach(cfg => {
        const el = chartRef.value[cfg.key]
        if (!el) return

        if (chartInstance.value[cfg.key]) chartInstance.value[cfg.key].dispose()

        const instance = echarts.init(el, isDarkMode() ? 'dark' : null)
        instance.setOption(createOption(cfg.key, cfg.label, chartDataStore.value[cfg.key].labels, chartDataStore.value[cfg.key].values, cfg.color, chartDataStore.value[cfg.key].units))
        chartInstance.value[cfg.key] = instance
    })
}

const initChartsRouter = () => {
    const documentStyle = getComputedStyle(document.documentElement);
    const configs = [
        { key: 'cpu', label: 'CPU Usage', color: documentStyle.getPropertyValue('--p-green-500'), units: '' },
        { key: 'temp', label: 'CPU Temperature', color: documentStyle.getPropertyValue('--p-sky-500'), units: '' },
        { key: 'ram_used', label: 'Memory in Use', color: documentStyle.getPropertyValue('--p-yellow-500'), units: '' },
        { key: 'ram_total', label: 'Memory Available', color: documentStyle.getPropertyValue('--p-red-500'), units: '' },
        { key: 'disk_used', label: 'Disk Used', color: documentStyle.getPropertyValue('--p-purple-500'), units: '' },
        { key: 'disk_total', label: 'Disk Total', color: documentStyle.getPropertyValue('--p-indigo-500'), units: '' },
        { key: 'port', label: 'Network Speed', color: documentStyle.getPropertyValue('--p-lime-500'), units: '' },
    ]

    configs.forEach(cfg => {
        const el = routerRef.value[cfg.key]
        if (!el) return

        if (routerInstance.value[cfg.key]) routerInstance.value[cfg.key].dispose()

        const instance = echarts.init(el, isDarkMode() ? 'dark' : null)
        if (cfg.key === 'port') {
            instance.setOption(createRouterNetworkOption(cfg.key, cfg.label, routerDataStore.value[cfg.key].labels, routerDataStore.value[cfg.key].values, cfg.color, routerDataStore.value[cfg.key].units))
        } else {
            instance.setOption(createOption(cfg.key, cfg.label, routerDataStore.value[cfg.key].labels, routerDataStore.value[cfg.key].values, cfg.color, routerDataStore.value[cfg.key].units))
        }
        routerInstance.value[cfg.key] = instance
    })
}

const initData = () => {
    dataDevice.value = Array.isArray(datas.lists) ? [...datas.lists] : []
    loading.value = false
}

const get_detail = (dt, type) => {
    setHostname.value = dt
    setHostType.value = type
    setRamRouter.value = 0
    const form = useForm({ host: dt, type: type, init: true })
    submitted.value = true

    form.post('/monitoring/detail', {
        resetOnSuccess: true,
        onSuccess: (res) => {
            const messages = res.props.flash.message
            if (messages.status === 'success') {
                fetch_detail(messages.summary, type)
                const isUptime = messages.data.uptime
                dataUptime.value = isUptime

                if (type === 'server') {                    
                    // init data for chart
                    chartInitData('load', messages.data.load)
                    chartInitData('cpu', messages.data.cpu)
                    chartInitData('mem_use', messages.data.memory)
                    chartInitData('mem_ava', messages.data.memory)
                    chartInitData('disk_r', messages.data.disk)
                    chartInitData('disk_w', messages.data.disk)
                    chartInitData('net_in', messages.data.network)
                    chartInitData('net_out', messages.data.network)

                    initCpuDetailCharts()
                    updateCpuDetails(messages.data.cpu, 'init')
                } else if (type === 'router') {
                    setRamRouter.value = messages.data.ram_total.value
                    dataEther.value = messages.data.ports
                    // activePort.value = messages.data.ports.filter(port => port.status === 'up')
                    activePort.value = messages.data.ports.filter(port => port.status === 'up').map(item => ({name: item.name.replaceAll('_', ' '), value: item.name}))
                    totalDrops.value = messages.data.ports.reduce((total, port) => {
                        return total + (port.drops || 0)
                    }, 0)
                    if (activePort.value.length > 0) {
                        selectedPort.value = activePort.value[0]
                    } else {
                        selectedPort.value = {name: null, value: null}
                    }

                    chartInitRouterData('cpu', messages.data.cpu)
                    chartInitRouterData('temp', messages.data.cpu_temp)
                    chartInitRouterData('ram_used', messages.data.ram_used)
                    chartInitRouterData('ram_total', messages.data.ram_total)
                    chartInitRouterData('disk_used', messages.data.disk_used)
                    chartInitRouterData('disk_total', messages.data.disk_total)
                    chartInitRouterData('port', messages.data.ports.filter(port => port.name === selectedPort.value.value)[0])
                }

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

const chartInitData = (key, data) => {
    if (data && data !== undefined) {
        let value = null
        const unit  = data.unit ?? ''
        const time  = data.time ?? 0
        switch (key) {
            case 'load':
                value = data.total
                break
            case 'cpu':
                value = data.total
                break
            case 'mem_use':
                value = data.used
                break
            case 'mem_ava':
                value = data.avail
                break
            case 'disk_r':
                value = data.read
                break
            case 'disk_w':
                value = data.write
                break
            case 'net_in':
                value = data.in
                break
            case 'net_out':
                value = data.out
                break
            case 'time':
        }

        // const setDate = moment(new Date(time * 1000)).format('HH:mm:ss')
        // const dataLabel = [setDate]
        // const datavalue = [value]

        chartDataStore.value[key].labels = data.time.slice(0, 15).map(d => moment.unix(d).format('HH:mm:ss')).reverse()
        chartDataStore.value[key].values = value.slice(0, 15).map(d => d).reverse()
        chartDataStore.value[key].units = (unit === 'percentage' ? '%' : (unit === 'load' ? '' : unit))
    }   
}

const chartInitRouterData = (key, data) => {
    console.log(data, routerDataStore.value)
    if (data && data !== undefined) {
        if (key === 'port') {
            const unit = 'mbps'
            let value = []
            value[0] = [data.out_mbps ?? 0]
            value[1] = [data.in_mbps ?? 0]
            const time = moment().format('HH:mm:ss')

            routerDataStore.value[key].labels = [time]
            routerDataStore.value[key].values = value
            routerDataStore.value[key].units = unit

            portUp.value    = data.out_mbps
            portDown.value  = data.in_mbps

            // routerInstance.value[key]?.setOption({
            //     xAxis: { data: time },
            //     series: [{ data: value[0] }, { data: value[1] }]
            // })
        } else {
            const convert = data.unit === 'By' ? formatBytesAuto(data.value) : (data.value +' '+ data.unit)
            // const convert = data.unit === 'By' ? formatBytesAuto(data.value) : (key === 'port' ? (data.in_mbps + ' mbps') : (data.value +' '+ data.unit))
            const value = parseFloat(convert.split(' ')[0]) ?? 0
            const unit  = convert.split(' ')[1] ?? data.unit
            const time  = data.time ?? 0

            routerDataStore.value[key].labels = [moment.unix(time).format('HH:mm:ss')]
            routerDataStore.value[key].values = [value]
            routerDataStore.value[key].units = (unit === 'percentage' ? '%' : (unit === 'load' ? '' : unit))
        }
    }   
}

const get_continues = () => {
    const form = useForm({ host: setHostname.value, type: setHostType.value, init: false })
    submitted.value = true

    form.post('/monitoring/detail', {
        resetOnSuccess: true,
        onSuccess: (res) => {
            const messages = res.props.flash.message
            if (messages.status === 'success') {
                fetch_detail(messages.summary, setHostType.value)
                const isUptime = messages.data.uptime
                dataUptime.value = isUptime

                if (setHostType.value === 'server') {
                    chart_continues('load', messages.data.load)
                    chart_continues('cpu', messages.data.cpu)
                    chart_continues('mem_use', messages.data.memory)
                    chart_continues('mem_ava', messages.data.memory)
                    chart_continues('disk_r', messages.data.disk)
                    chart_continues('disk_w', messages.data.disk)
                    chart_continues('net_in', messages.data.network)
                    chart_continues('net_out', messages.data.network)

                    initCpuDetailCharts()
                    updateCpuDetails(messages.data.cpu, 'loop')
                } else if (setHostType.value === 'router') {
                    setRamRouter.value = messages.data.ram_total.value
                    dataEther.value = messages.data.ports
                    activePort.value = messages.data.ports.filter(port => port.status === 'up').map(item => ({name: item.name.replaceAll('_', ' '), value: item.name}))
                    totalDrops.value = messages.data.ports.reduce((total, port) => {
                        return total + (port.drops || 0)
                    }, 0)
                    console.log('ports', messages.data.ports.filter(port => port.name === selectedPort.value.value)[0])

                    chart_router_continues('cpu', messages.data.cpu)
                    chart_router_continues('temp', messages.data.cpu_temp)
                    chart_router_continues('ram_used', messages.data.ram_used)
                    chart_router_continues('ram_total', messages.data.ram_total)
                    chart_router_continues('disk_used', messages.data.disk_used)
                    chart_router_continues('disk_total', messages.data.disk_total)
                    chart_router_continues('port', messages.data.ports.filter(port => port.name === selectedPort.value.value)[0])
                }
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

const fetch_detail = (data, type) => {
    if (data) {
        if (type === 'server') {
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
        } else if (type === 'router') {
            data = data[0]
            console.log('data', data)
            // host, ip, contact, loc, model, desc, version, alert,
            detailCard.value.version    = data.v
            detailCard.value.ip         = data.labels.address
            detailCard.value.hostname   = data.labels._hostname
            detailCard.value.architecture = data.labels.model
            detailCard.value.os         = data.labels.description
            detailCard.value.ram        = formatBytesAuto(parseInt(setRamRouter.value) ?? 0)
            detailCard.value.core       = ''
            detailCard.value.cpu        = ''
            detailCard.value.virtualization = data.labels.contact
            detailCard.value.uptime     = dataUptime.value
            detailCard.value.tz         = data.labels.location
            detailCard.value.alarm.normal   = data.health.alerts.clear
            detailCard.value.alarm.warning  = data.health.alerts.warning
            detailCard.value.alarm.critical = data.health.alerts.critical
        }

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

const formatBitsAuto = (bits, decimals = 2) => {
    if (!+bits) return '0 bps';
    
    // Gunakan 1000 untuk standar bit/komunikasi data
    const k = 1000;
    const dm = decimals < 0 ? 0 : decimals;
    // Satuan dalam bit (b, kb, Mb, Gb, Tb, Pb)
    const sizes = ['bps', 'kbps', 'Mbps', 'Gbps', 'Tbps', 'Pbps'];
    
    const i = Math.floor(Math.log(bits) / Math.log(k));
    const index = Math.min(i, sizes.length - 1);
    
    return `${parseFloat((bits / Math.pow(k, index)).toFixed(dm))} ${sizes[index]}`;
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

const chart_continues = (key, data) => {
    if (data && data !== undefined) {
        let value = 0
        const unit  = data.unit ?? ''
        const time  = data.time ?? 0
        switch (key) {
            case 'load':
                value = data.total
                break
            case 'cpu':
                value = data.total
                break
            case 'mem_use':
                value = data.used
                break
            case 'mem_ava':
                value = data.avail
                break
            case 'disk_r':
                value = data.read
                break
            case 'disk_w':
                value = data.write
                break
            case 'net_in':
                value = data.in
                break
            case 'net_out':
                value = data.out
                break
            case 'time':
        }

        // const setDate = moment(new Date(time)).format('HH:mm:ss')
        const setDate = moment.unix(time).format('HH:mm:ss')
        const store = chartDataStore.value[key]
        store.labels.push(setDate)
        store.values.push(value)
        store.units = (unit === 'percentage' ? '%' : (unit === 'load' ? '' : unit))

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

const chart_router_continues = (key, data) => {
    if (data && data !== undefined) {
        if (key === 'port') {
            const unit = 'mbps'
            let value = []
            value[0] = data.out_mbps ?? 0
            value[1] = data.in_mbps ?? 0
            const time = moment().format('HH:mm:ss')

            const store = routerDataStore.value[key]
            // console.log('val', store.values)

            store.labels.push(time)
            store.values[0].push(value[0])
            store.values[1].push(value[1])
            store.units = unit

            if (store.labels.length > 20) {
                store.labels.shift()
                store.values[0].shift()
                store.values[1].shift()
            }

            portUp.value    = data.out_mbps
            portDown.value  = data.in_mbps

            routerInstance.value[key]?.setOption({
                xAxis: { data: store.labels },
                series: [{ data: store.values[0] }, { data: store.values[1] }]
            })
        } else {
            const convert = data.unit === 'By' ? formatBytesAuto(data.value) : (data.value +' '+ data.unit)
            // const convert = data.unit === 'By' ? formatBytesAuto(data.value) : (key === 'port' ? (data.in_mbps + ' mbps') : (data.value +' '+ data.unit))
            const value = parseFloat(convert.split(' ')[0]) ?? 0
            const unit  = convert.split(' ')[1] ?? data.unit
            const time  = data.time ?? 0
            
            const setDate = moment.unix(time).format('HH:mm:ss')
            const store = routerDataStore.value[key]

            store.labels.push(setDate)
            store.values.push(value)
            store.units = (unit === 'percentage' ? '%' : (unit === 'load' ? '' : unit))

            if (store.labels.length > 20) {
                store.labels.shift()
                store.values.shift()
            }

            routerInstance.value[key]?.setOption({
                xAxis: { data: store.labels },
                series: [{ data: store.values }]
            })
        }
    }
}

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
        if (setHostType.value === 'server') {
            initCharts()
        } else if (setHostType.value === 'router') {
            initChartsRouter()
        }

        timer = setInterval(() => { get_continues() }, 5000)
        window.addEventListener('resize', handleResize)
        console.log('started')
    } else {
        clearInterval(timer)
        timer = null
        
        Object.values(chartInstance.value).forEach(i => i.dispose())
        Object.values(cpuChartInstances.value).forEach(i => i.dispose())
        Object.values(routerInstance.value).forEach(i => i.dispose())

        window.addEventListener('resize', handleResize)
        console.log('stopped')
    }
})

const handleResize = () => Object.values(chartInstance.value).forEach(i => i.resize())

const initCpuDetailCharts = () => {
    cpuMetrics.forEach(metric => {
        const el = document.getElementById(`chart-cpu-${metric}`);
        if (!el) return;

        const instance = echarts.init(el, isDarkMode() ? 'dark' : null);
        const values = cpuDataStore.value[metric];
        
        instance.setOption({
            title: { 
                text: metric.toUpperCase(), 
                left: 'center', 
                textStyle: { fontSize: 12, fontWeight: 'bold' } 
            },
            // grid: { top: 30, bottom: 20, left: 30, right: 10 },
            tooltip: {
                formatter: '{a} <br/>{b} : {c}%'
            },
            series: [{
                name: metric,
                type: 'gauge',
                startAngle: 215,
                endAngle: -35,
                min: 0,
                max: 100,
                splitNumber: 10,
                itemStyle: {
                    // Warna akan berubah dinamis berdasarkan beban CPU
                    color: values.value > 85 ? '#ef4444' : values.value > 60 ? '#f59e0b' : '#10b981',
                    shadowColor: 'rgba(0,138,255,0.45)',
                    shadowBlur: 10,
                    shadowOffsetX: 2,
                    shadowOffsetY: 2
                },
                // green dot
                progress: {
                    show: true,
                    roundCap: true,
                    width: 9
                },
                // speed arrow
                // pointer: {
                //     icon: 'path://M12.8,0.7l12,18H17.8v22h-10v-22H0.8L12.8,0.7z',
                //     color: 'cyan',
                //     length: '12%',
                //     width: 10,
                //     offsetCenter: [0, '-40%'],
                //     itemStyle: {
                //         color: 'auto'
                //     }
                // },
                // cube line
                axisLine: {
                    roundCap: true,
                    lineStyle: {
                        width: 9
                    }
                },
                // speed line
                axisTick: {
                    distance: 0,
                    splitNumber: 2,
                    lineStyle: {
                        width: 2,
                        color: '#999'
                    }
                },
                // speed line
                splitLine: {
                    distance: 0,
                    length: 12,
                    lineStyle: {
                        width: 3,
                        color: '#999'
                    }
                },
                // speed label
                axisLabel: {
                    distance: 10,
                    color: '#999',
                    fontSize: 7
                },
                title: {
                    show: false
                },
                detail: {
                    // backgroundColor: '#fff',
                    // borderColor: '#999',
                    // borderWidth: 1,
                    width: '60%',
                    lineHeight: 40,
                    height: 40,
                    // borderRadius: 4,
                    offsetCenter: [0, '35%'],
                    valueAnimation: true,
                    formatter: function (value) {
                        return '{value|' + value.toFixed(2) + '}{unit|%}';
                    },
                    rich: {
                        value: {
                            fontSize: 20,
                            fontWeight: 'bolder',
                            color: '#777',
                            padding: [0, 0, -60, 0]
                        },
                        unit: {
                            fontSize: 12,
                            color: '#999',
                            padding: [0, 0, -60, 2]
                        }
                    }
                },
                data: [
                    { value: values.value }
                ]
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

const updateCpuDetails = (rawData, type) => {
    const dataRow = Object.entries(rawData.datas).map(([key, val]) => ({
        label: key,
        value: (type === 'init' ? val[0] : val)
    }));
    // const timeLabel = moment.unix(dataRow.time).format('HH:mm:ss');

    dataRow.forEach((metric, i) => {
        if (metric.label === 'time') return

        // console.log(metric, cpuDataStore.value)
        // const stores = cpuDataStore.value[metric.label]

        // stores.labels = [metric.label]
        // stores.values = [metric.value > 0 ? metric.value.toFixed(2) : metric.value]

        cpuChartInstances.value[metric.label]?.setOption({
            series: [
                {
                    data: [{ value: metric.value }],
                    itemStyle: {
                        color: metric.value > 85 ? '#ef4444' : metric.value > 60 ? '#f59e0b' : '#10b981'
                    }
                }
            ]
        })
    })
}

const clearFilter = () => {
    initFilters()
}

onMounted(() => {
    initData()
    initFilters()
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
                    <Column field="hostname" header="Nama Device" style="width: 30%">
                        <template #body="{ data }">
                            {{ data.hostname }}
                        </template>
                    </Column>
                    <Column field="hops" header="Jenis" style="width: 10%">
                        <template #body="{ data }">
                            {{ data.type === 'server' ? 'Server' : 'Router/Switch' }}
                        </template>
                    </Column>
                    <Column field="" header="Status" style="width: 10%">
                        <template #body="slotProps">
                            <Tag :severity="slotProps.data.reachable ? 'success' : 'danger'" :value="slotProps.data.reachable ? 'Online' : 'Offline'"></Tag>
                        </template>
                    </Column>
                    <Column field="ip" header="Alamat IP" style="width: 10%" />
                    <Column field="label" header="Label" style="width: 25%" />

                    <Column header="Opsi" style="width: 10%; text-align: right;" class="justify-items-center">
                        <template #body="slotProps">
                            <div class="w-full text-center">
                                <Button icon="pi pi-chart-bar" @click="get_detail(slotProps.data.hostname, slotProps.data.type)" v-tooltip.bottom="'Lihat Detail'" :severity="slotProps.data.reachable ? 'success' : 'danger'" variant="text" raised rounded :disabled="!slotProps.data.reachable" />
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

    <Dialog v-model:visible="detailDialog" maximizable modal :header="`Detail ${setHostType === 'router' ? 'router/switch' : setHostType}`" :style="{width: '90rem'}" :breakpoints="{ '1199px': '75vw', '575px': '90vw' }">

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
                                <span class="block text-muted-color font-medium mb-4">IP Address</span>
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
                                <span class="block text-muted-color font-medium mb-4">Hostname</span>
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
                                <span class="block text-muted-color font-medium mb-4">{{ setHostType === 'server' ? 'Architecture' : 'Model' }}</span>
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
                                <span class="block text-muted-color font-medium mb-4">{{ setHostType === 'server' ? 'Operating System' : 'Description' }}</span>
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
                <div class="col-span-6 lg:col-span-6 xl:col-span-3" v-if="setHostType == 'server'">
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
                <div class="col-span-6 lg:col-span-6 xl:col-span-3" v-if="setHostType == 'server'">
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
                                <span class="block text-muted-color font-medium mb-4">{{ setHostType === 'server' ? 'Virtualization' : 'Contact' }}</span>
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
                                <span class="block text-muted-color font-medium mb-4">{{ setHostType === 'server' ? 'Timezone' : 'Location' }}</span>
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

        <div v-if="setHostType === 'server'">
            <Panel header="Real-time Performance" class="mb-5" toggleable :key="`perform-${setHostname.replaceAll(' ', '-')}`">
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

            <Panel :header="`CPU Details`" class="mb-5" toggleable :key="`cpu-${setHostname.replaceAll(' ', '-')}`">
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
        </div>
        <div v-else-if="setHostType == 'router'">
            <Panel header="Real-time Performance" class="mb-5" toggleable :key="`is-router-${setHostname.replaceAll(' ', '-')}`">
                <div class="grid grid-cols-12 gap-8">
                    <div class="col-span-12 lg:col-span-6 xl:col-span-6">
                        <div class="card mb-0 h-full">
                            <h5 class="text-center">CPU Usage %</h5>
                            <div :ref="el => routerRef['cpu'] = el" class="h-[20rem]"></div>
                        </div>
                    </div>
                    <div class="col-span-12 lg:col-span-6 xl:col-span-6">
                        <div class="card mb-0 h-full">
                            <h5 class="text-center">CPU Temperature</h5>
                            <div :ref="el => routerRef['temp'] = el" class="h-[20rem]"></div>
                        </div>
                    </div>
                    <div class="col-span-12 lg:col-span-6 xl:col-span-6">
                        <div class="card mb-0 h-full">
                            <h5 class="text-center">Memory Used</h5>
                            <div :ref="el => routerRef['ram_used'] = el" class="h-[20rem]"></div>
                        </div>
                    </div>
                    <!-- <div class="col-span-12 lg:col-span-6 xl:col-span-6">
                        <div class="card mb-0 h-full">
                            <h5 class="text-center">Memory Total</h5>
                            <div :ref="el => routerRef['ram_total'] = el" class="h-[20rem]"></div>
                        </div>
                    </div> -->
                    <div class="col-span-12 lg:col-span-6 xl:col-span-6">
                        <div class="card mb-0 h-full">
                            <h5 class="text-center">Disk Used</h5>
                            <div :ref="el => routerRef['disk_used'] = el" class="h-[20rem]"></div>
                        </div>
                    </div>
                    <!-- <div class="col-span-12 lg:col-span-6 xl:col-span-6">
                        <div class="card mb-0 h-full">
                            <h5 class="text-center">Disk Total</h5>
                            <div :ref="el => routerRef['disk_total'] = el" class="h-[20rem]"></div>
                        </div>
                    </div> -->
                    <div class="col-span-12 lg:col-span-12 xl:col-span-12">
                        <div class="card mb-0 h-full text-center">
                            <Select v-model="selectedPort" :options="activePort" optionLabel="name" name="port" placeholder="Select Active Port" class="w-6/12 mb-5" />
                            <h5 class="text-center">
                                <!-- Network {{ selectedPort.name ?? '' }} <br><br> -->
                                 Current Network Traffic <br><br>
                                <Tag icon="pi pi-arrow-circle-up" severity="danger" :value="portUp + ' mbps'" v-tooltip="'Upload Speed'"></Tag> &nbsp;&nbsp;
                                <Tag icon="pi pi-arrow-circle-down" severity="success" :value="portDown + ' mbps'" v-tooltip="'Download Speed'"></Tag>
                            </h5>
                            <div :ref="el => routerRef['port'] = el" class="h-[20rem]"></div>
                        </div>
                    </div>
                </div>
            </Panel>

            <Card>
                <template #content>
                    <DataTable v-model:filters="filters" v-model:first="firstEther" :value="dataEther" paginator showGridlines :rows="15" :rowsPerPageOptions="[5, 10, 15, 20, 50]" tableStyle="min-width: 50rem" :loading="loading">
                        <template #header>
                            <div class="flex justify-between">
                                <div class="flex flex-wrap gap-4">
                                    <h4>
                                        Network Port Monitoring <br>
                                        Active Port {{ activePort.length +' / '+ dataEther.length }} | Total Drops {{ totalDrops }}
                                    </h4>
                                </div>
                                <IconField>
                                    <InputIcon>
                                        <i class="pi pi-search" />
                                    </InputIcon>
                                    <InputText v-model="filters['global'].value" placeholder="Pencarian" />
                                </IconField>
                            </div>
                        </template>
                        <template #empty><div class="w-full text-center">Tidak ada data.</div></template>
                        <template #loading><div class="w-full text-center">Memproses data. Harap menunggu.</div> </template>
                        <Column field="" header="#" style="width: 5%">
                            <template #body="{ index }">
                                <label>{{ firstEther + index + 1 }}</label>
                            </template>
                        </Column>
                        <Column field="" header="Port" style="width: 25%">
                            <template #body="slotProps">
                                {{ slotProps.data.name.replaceAll('_', ' ') }}
                            </template>
                        </Column>
                        <Column field="" header="Status" style="width: 25%">
                            <template #body="slotProps">
                                <Tag :severity="slotProps.data.status === 'up' ? 'success' : 'danger'" :value="slotProps.data.status"></Tag>
                            </template>
                        </Column>
                        <Column field="" header="Download (Mbps)" style="width: 15%">
                            <template #body="slotProps">
                                {{ slotProps.data.in_mbps }}
                            </template>
                        </Column>
                        <Column field="" header="Upload (Mbps)" style="width: 15%">
                            <template #body="slotProps">
                                {{ slotProps.data.out_mbps }}
                            </template>
                        </Column>
                        <Column field="" header="Packet Drops" style="width: 15%">
                            <template #body="slotProps">
                                {{ slotProps.data.drops }}
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
                </template>
            </Card>
        </div>

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