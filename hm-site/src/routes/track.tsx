import { createFileRoute, useNavigate } from "@tanstack/react-router";
import { useState, FormEvent } from "react";
import { Search, Package, MapPin, CheckCircle2, AlertCircle, Clock } from "lucide-react";

export const Route = createFileRoute("/track")({
  component: TrackPage,
});

function TrackPage() {
  const navigate = useNavigate({ from: Route.fullPath });
  const searchParams = Route.useSearch<{ ref?: string }>();
  const initialRef = searchParams.ref || "";

  const [reference, setReference] = useState(initialRef);
  const [loading, setLoading] = useState(false);
  const [data, setData] = useState<any>(null);
  const [error, setError] = useState<string | null>(null);

  useState(() => {
    if (initialRef) {
      handleSearch(initialRef);
    }
  });

  async function handleSearch(searchRef: string) {
    if (!searchRef.trim()) return;

    setLoading(true);
    setError(null);
    setData(null);

    navigate({ search: { ref: searchRef }, replace: true });

    try {
      const baseUrl = import.meta.env.VITE_API_BASE_URL || (import.meta.env.DEV ? "http://localhost:8000" : "https://system.hmcargoservices.com");
      const apiUrl = `${baseUrl}/api/track/ref/${encodeURIComponent(searchRef)}`;

      const res = await fetch(apiUrl);
      if (!res.ok) {
        if (res.status === 404) {
          throw new Error("لم نتمكن من العثور على شحنة بهذا الرقم.");
        }
        throw new Error("حدث خطأ أثناء جلب بيانات الشحنة.");
      }

      const json = await res.json();
      setData(json);
    } catch (err: any) {
      setError(err.message || "حدث خطأ غير متوقع.");
    } finally {
      setLoading(false);
    }
  }

  function onSubmit(e: FormEvent) {
    e.preventDefault();
    handleSearch(reference);
  }

  return (
    <div dir="rtl" className="min-h-screen bg-background text-foreground font-sans selection:bg-accent/30 selection:text-white">
      {/* Cinematic Header */}
      <div className="relative pt-24 pb-16 px-4 sm:px-6 lg:px-8 border-b border-border/40 overflow-hidden">
        {/* Glow Effects */}
        <div className="absolute top-0 left-1/2 -translate-x-1/2 w-[800px] h-[300px] opacity-20 pointer-events-none" 
             style={{ background: 'radial-gradient(circle at top, var(--accent), transparent 70%)' }} />
             
        <div className="max-w-3xl mx-auto relative z-10 text-center">
          <p className="eyebrow mx-auto mb-4 tracking-widest text-accent text-sm font-semibold uppercase opacity-90">HM Cargo Services</p>
          <h1 className="text-5xl md:text-6xl font-display font-medium text-white mb-6 drop-shadow-xl">تتبع الشحنة</h1>
          <p className="text-muted-foreground text-lg md:text-xl font-medium max-w-xl mx-auto">
            أدخل الرقم المرجعي لشحنتك لمعرفة حالتها ومسارها المباشر
          </p>
        </div>
      </div>

      <div className="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        {/* Search Form */}
        <form onSubmit={onSubmit} className="mb-16">
          <div className="max-w-xl mx-auto relative group">
            <div className="absolute inset-0 bg-accent/20 rounded-2xl blur-xl opacity-0 group-hover:opacity-100 group-focus-within:opacity-100 transition-opacity duration-500"></div>
            <div className="relative flex items-center bg-card border border-border/80 focus-within:border-accent/50 rounded-2xl shadow-2xl p-2 transition-all">
              <div className="flex-1 px-4 relative">
                <input
                  type="text"
                  value={reference}
                  onChange={(e) => setReference(e.target.value.toUpperCase())}
                  placeholder="مثال: HM-2026-000001"
                  className="w-full bg-transparent text-xl font-mono text-white placeholder-muted-foreground focus:outline-none text-left tracking-wider"
                  dir="ltr"
                  required
                />
              </div>
              <button
                type="submit"
                disabled={loading || !reference}
                className="btn-primary rounded-xl px-8 py-3.5 flex items-center gap-2 flex-shrink-0"
              >
                {loading ? (
                  <span className="flex items-center gap-2">
                    <div className="w-4 h-4 border-2 border-primary-foreground/30 border-t-primary-foreground rounded-full animate-spin"></div>
                    جاري البحث...
                  </span>
                ) : (
                  <>
                    <Search className="w-5 h-5" />
                    <span>تتبع</span>
                  </>
                )}
              </button>
            </div>
          </div>
        </form>

        {error && (
          <div className="max-w-xl mx-auto animate-in fade-in slide-in-from-bottom-4 duration-500">
            <div className="bg-destructive/10 border border-destructive/20 rounded-2xl p-6 flex flex-col items-center justify-center gap-3 text-center shadow-lg backdrop-blur-sm">
              <AlertCircle className="w-8 h-8 text-destructive" />
              <span className="text-lg text-white font-medium">{error}</span>
            </div>
          </div>
        )}

        {data && (
          <div className="space-y-8 animate-in fade-in slide-in-from-bottom-8 duration-700">
            {/* Header Card */}
            <div className="bg-card/50 backdrop-blur-md border border-border/50 rounded-3xl p-8 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-6 shadow-2xl">
              <div>
                <p className="text-muted-foreground text-sm font-medium tracking-wide mb-2 uppercase">رقم الشحنة</p>
                <h2 className="text-3xl font-display text-white tracking-widest">{data.reference}</h2>
              </div>
              <div className="text-right flex-shrink-0">
                <p className="text-muted-foreground text-sm font-medium tracking-wide mb-2 uppercase">الحالة الحالية</p>
                <div className={`inline-flex items-center gap-2 px-5 py-2.5 rounded-full font-bold shadow-lg ${
                  data.status === 'delivered' 
                    ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20' 
                    : 'bg-accent/10 text-accent border border-accent/20'
                }`}>
                  {data.status === 'delivered' ? <CheckCircle2 className="w-5 h-5" /> : <Clock className="w-5 h-5 animate-pulse" />}
                  {data.status_label}
                </div>
              </div>
            </div>

            <div className="grid grid-cols-1 md:grid-cols-12 gap-8">
              {/* Info Column */}
              <div className="md:col-span-5 space-y-8">
                {/* Shipment Details */}
                <div className="bg-card border border-border/40 rounded-3xl p-6 sm:p-8 shadow-xl relative overflow-hidden">
                  <div className="absolute top-0 right-0 w-32 h-32 bg-accent/5 rounded-full blur-3xl"></div>
                  
                  <h3 className="text-xl font-display text-white mb-6 flex items-center gap-3">
                    <Package className="w-5 h-5 text-accent" /> تفاصيل الشحنة
                  </h3>
                  
                  <div className="space-y-5">
                    <div>
                      <span className="block text-xs font-semibold text-muted-foreground uppercase tracking-widest mb-1">المستلم</span>
                      <span className="block text-lg font-medium text-white">{data.recipient}</span>
                    </div>
                    <div className="w-full h-px bg-border/40"></div>
                    <div>
                      <span className="block text-xs font-semibold text-muted-foreground uppercase tracking-widest mb-1">المسار</span>
                      <span className="block text-lg font-medium text-white">{data.route || 'غير محدد'}</span>
                    </div>
                    <div className="w-full h-px bg-border/40"></div>
                    <div>
                      <span className="block text-xs font-semibold text-muted-foreground uppercase tracking-widest mb-1">حالة الطرود</span>
                      <div className="flex items-center gap-2 mt-1">
                        <span className="text-2xl font-bold text-accent font-mono">{data.progress.arrived}</span>
                        <span className="text-muted-foreground">من أصل</span>
                        <span className="text-xl font-bold text-white font-mono">{data.progress.total}</span>
                      </div>
                    </div>
                  </div>
                </div>

                {/* Financials */}
                <div className="bg-card border border-border/40 rounded-3xl p-6 sm:p-8 shadow-xl relative overflow-hidden">
                  <h3 className="text-xl font-display text-white mb-6">البيانات المالية</h3>
                  
                  <div className="space-y-4">
                    <div className="flex justify-between items-end p-4 rounded-xl bg-background/50 border border-border/30">
                      <span className="text-sm font-medium text-muted-foreground">الإجمالي</span>
                      <span className="font-bold text-white text-xl font-mono">
                        {data.financial.final_charge !== null ? `$${(data.financial.final_charge / 100).toFixed(2)}` : 'يحدد لاحقاً'}
                      </span>
                    </div>
                    <div className="flex justify-between items-end p-4 rounded-xl bg-background/50 border border-border/30">
                      <span className="text-sm font-medium text-muted-foreground">المدفوع</span>
                      <span className="font-bold text-emerald-400 text-xl font-mono">
                        {data.financial.paid !== null ? `$${(data.financial.paid / 100).toFixed(2)}` : '-'}
                      </span>
                    </div>
                    <div className="flex justify-between items-end p-5 rounded-xl bg-accent/5 border border-accent/20 shadow-inner">
                      <span className="text-base font-semibold text-white">المتبقي</span>
                      <span className="font-bold text-accent text-2xl font-mono drop-shadow-md">
                        {data.financial.remaining !== null ? `$${(data.financial.remaining / 100).toFixed(2)}` : 'يحدد لاحقاً'}
                      </span>
                    </div>
                  </div>
                </div>
              </div>

              {/* Journey Timeline Column */}
              <div className="md:col-span-7">
                <div className="bg-card border border-border/40 rounded-3xl p-6 sm:p-8 shadow-xl h-full">
                  <h3 className="text-xl font-display text-white mb-8 flex items-center gap-3">
                    <MapPin className="w-5 h-5 text-accent" /> سجل الرحلة
                  </h3>

                  <div className="relative border-r-2 border-border/30 mr-4 pr-8 pt-2 pb-6 space-y-10">
                    {data.timeline && data.timeline.length > 0 ? (
                      data.timeline.map((event: any, i: number) => {
                        const isLatest = i === 0;
                        return (
                          <div key={i} className="relative">
                            {/* Timeline Node */}
                            <div className={`absolute -right-[43px] top-1.5 rounded-full ring-4 ring-card z-10 
                              ${isLatest ? 'w-5 h-5 bg-accent ring-accent/20 shadow-[0_0_15px_rgba(255,121,0,0.5)]' : 'w-4 h-4 bg-muted-foreground/30'}`}>
                            </div>
                            
                            <div className={`transition-all duration-300 ${isLatest ? 'opacity-100' : 'opacity-70 hover:opacity-100'}`}>
                              <p className={`font-semibold text-lg ${isLatest ? 'text-white' : 'text-muted-foreground'}`}>
                                {event.label}
                              </p>
                              <p className="text-muted-foreground text-sm mt-1.5 dir-ltr text-right font-mono tracking-wide">
                                {event.occurred_at}
                              </p>
                            </div>
                          </div>
                        );
                      })
                    ) : (
                      <p className="text-muted-foreground text-center py-8">لا توجد تحديثات للرحلة بعد</p>
                    )}
                  </div>
                </div>
              </div>
            </div>
          </div>
        )}
      </div>
    </div>
  );
}
