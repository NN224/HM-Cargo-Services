import { createFileRoute, useNavigate } from "@tanstack/react-router";
import { useState, FormEvent } from "react";
import { Search, Package, MapPin, CheckCircle2, AlertCircle, Clock } from "lucide-react";

export const Route = createFileRoute("/track")({
  component: TrackPage,
});

// Since the marketing site is usually LTR, we enforce RTL layout for this page
function TrackPage() {
  const navigate = useNavigate({ from: Route.fullPath });
  const searchParams = Route.useSearch<{ ref?: string }>();
  const initialRef = searchParams.ref || "";

  const [reference, setReference] = useState(initialRef);
  const [loading, setLoading] = useState(false);
  const [data, setData] = useState<any>(null);
  const [error, setError] = useState<string | null>(null);

  // Automatically fetch if we arrived with a ref
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

    // Update URL without reloading
    navigate({ search: { ref: searchRef }, replace: true });

    try {
      // In production, this would be system.hmcargoservices.com
      const apiUrl = import.meta.env.DEV
        ? "http://localhost:8000/api/track/ref/" + encodeURIComponent(searchRef)
        : "https://system.hmcargoservices.com/api/track/ref/" + encodeURIComponent(searchRef);

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
    <div dir="rtl" className="font-['Cairo'] min-h-screen bg-background text-foreground py-12 px-4 sm:px-6 lg:px-8">
      <div className="max-w-3xl mx-auto">
        <div className="text-center mb-12">
          <h1 className="text-4xl font-bold text-white mb-4">تتبع شحنتك</h1>
          <p className="text-muted-foreground text-lg">
            أدخل الرقم المرجعي لشحنتك لمعرفة حالتها ومسارها المباشر
          </p>
        </div>

        <form onSubmit={onSubmit} className="relative mb-12 max-w-xl mx-auto">
          <div className="relative flex items-center">
            <input
              type="text"
              value={reference}
              onChange={(e) => setReference(e.target.value)}
              placeholder="مثال: HM-2026-000001"
              className="w-full bg-card border border-border rounded-full py-4 px-6 pr-12 text-lg text-white placeholder-muted-foreground focus:outline-none focus:ring-2 focus:ring-primary transition-all text-left"
              dir="ltr"
              required
            />
            <Search className="absolute right-5 text-muted-foreground w-6 h-6" />
            <button
              type="submit"
              disabled={loading || !reference}
              className="absolute left-2 bg-primary hover:bg-primary/90 text-primary-foreground rounded-full px-6 py-2.5 font-medium transition-colors disabled:opacity-50"
            >
              {loading ? "جاري البحث..." : "تتبع"}
            </button>
          </div>
        </form>

        {error && (
          <div className="bg-destructive/10 border border-destructive/20 rounded-xl p-6 text-center text-destructive flex items-center justify-center gap-3">
            <AlertCircle className="w-6 h-6" />
            <span className="text-lg">{error}</span>
          </div>
        )}

        {data && (
          <div className="space-y-8 animate-in fade-in slide-in-from-bottom-4 duration-500">
            {/* Header Card */}
            <div className="bg-card border border-border rounded-2xl p-6 sm:p-8 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-6">
              <div>
                <p className="text-muted-foreground mb-1">رقم الشحنة</p>
                <h2 className="text-2xl font-bold text-white font-mono tracking-wider">{data.reference}</h2>
              </div>
              <div className="text-right">
                <p className="text-muted-foreground mb-1">الحالة الحالية</p>
                <div className="inline-flex items-center gap-2 bg-primary/10 text-primary px-4 py-2 rounded-full font-semibold">
                  {data.status === 'delivered' ? <CheckCircle2 className="w-5 h-5" /> : <Clock className="w-5 h-5" />}
                  {data.status_label}
                </div>
              </div>
            </div>

            <div className="grid grid-cols-1 md:grid-cols-2 gap-8">
              {/* Financials & Info */}
              <div className="space-y-6">
                <div className="bg-card border border-border rounded-2xl p-6">
                  <h3 className="text-xl font-semibold text-white mb-6 flex items-center gap-2">
                    <Package className="w-5 h-5 text-primary" /> تفاصيل الشحنة
                  </h3>
                  
                  <div className="space-y-4">
                    <div className="flex justify-between items-center py-3 border-b border-border/50">
                      <span className="text-muted-foreground">المستلم</span>
                      <span className="font-medium text-white">{data.recipient}</span>
                    </div>
                    <div className="flex justify-between items-center py-3 border-b border-border/50">
                      <span className="text-muted-foreground">المسار</span>
                      <span className="font-medium text-white">{data.route || 'غير محدد'}</span>
                    </div>
                    <div className="flex justify-between items-center py-3 border-b border-border/50">
                      <span className="text-muted-foreground">عدد الطرود الواصلة</span>
                      <span className="font-medium text-white">
                        <span className="text-primary">{data.progress.arrived}</span> من {data.progress.total}
                      </span>
                    </div>
                  </div>
                </div>

                <div className="bg-card border border-border rounded-2xl p-6">
                  <h3 className="text-xl font-semibold text-white mb-6">البيانات المالية</h3>
                  
                  <div className="space-y-4">
                    <div className="flex justify-between items-center py-3 border-b border-border/50">
                      <span className="text-muted-foreground">المبلغ الإجمالي</span>
                      <span className="font-medium text-white font-mono">
                        {data.financial.final_charge !== null ? `$${(data.financial.final_charge / 100).toFixed(2)}` : 'يحدد لاحقاً'}
                      </span>
                    </div>
                    <div className="flex justify-between items-center py-3 border-b border-border/50">
                      <span className="text-muted-foreground">المدفوع</span>
                      <span className="font-medium text-emerald-500 font-mono">
                        {data.financial.paid !== null ? `$${(data.financial.paid / 100).toFixed(2)}` : '-'}
                      </span>
                    </div>
                    <div className="flex justify-between items-center py-3">
                      <span className="text-muted-foreground">المتبقي</span>
                      <span className="font-bold text-white text-lg font-mono">
                        {data.financial.remaining !== null ? `$${(data.financial.remaining / 100).toFixed(2)}` : 'يحدد لاحقاً'}
                      </span>
                    </div>
                  </div>
                </div>
              </div>

              {/* Journey Timeline */}
              <div className="bg-card border border-border rounded-2xl p-6">
                <h3 className="text-xl font-semibold text-white mb-8 flex items-center gap-2">
                  <MapPin className="w-5 h-5 text-primary" /> سجل الرحلة
                </h3>

                <div className="relative border-r-2 border-border/50 mr-4 pr-6 space-y-8">
                  {data.timeline && data.timeline.length > 0 ? (
                    data.timeline.map((event: any, i: number) => (
                      <div key={i} className="relative">
                        <div className="absolute w-3 h-3 bg-primary rounded-full -right-[31px] top-1.5 ring-4 ring-card"></div>
                        <p className="text-white font-medium text-lg">{event.label}</p>
                        <p className="text-muted-foreground text-sm mt-1 dir-ltr text-right">{event.occurred_at}</p>
                      </div>
                    ))
                  ) : (
                    <p className="text-muted-foreground text-center">لا توجد تحديثات للرحلة بعد</p>
                  )}
                </div>
              </div>
            </div>
          </div>
        )}
      </div>
    </div>
  );
}
