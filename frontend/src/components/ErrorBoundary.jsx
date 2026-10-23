import React from 'react';
import { AlertTriangle, RefreshCw } from 'lucide-react';

export default class ErrorBoundary extends React.Component {
  constructor(props) {
    super(props);
    this.state = { hasError: false, error: null };
  }

  static getDerivedStateFromError(error) {
    return { hasError: true, error };
  }

  componentDidCatch(error, errorInfo) {
    console.error('ErrorBoundary caught an error:', error, errorInfo);
  }

  handleReset = () => {
    this.setState({ hasError: false, error: null });
    if (this.props.onReset) {
      this.props.onReset();
    } else {
      window.location.reload();
    }
  };

  render() {
    if (this.state.hasError) {
      return (
        <div className="p-6 sm:p-8 bg-white rounded-3xl border border-orange-200 shadow-sm max-w-2xl mx-auto my-6 text-center space-y-4 animate-fadeIn">
          <div className="w-14 h-14 rounded-2xl bg-orange-100 text-[#F05D24] flex items-center justify-center mx-auto shadow-xs">
            <AlertTriangle className="w-7 h-7" />
          </div>
          <div>
            <h3 className="text-lg font-bold text-gray-900">
              {this.props.title || 'Terjadi Kendala pada Tampilan Halaman'}
            </h3>
            <p className="text-xs text-gray-600 mt-1 max-w-md mx-auto leading-relaxed">
              {this.state.error?.message || 'Komponen mengalami kendala saat merender data. Silakan coba muat ulang.'}
            </p>
          </div>
          <div className="pt-2">
            <button
              type="button"
              onClick={this.handleReset}
              className="inline-flex items-center space-x-2 px-4 py-2.5 rounded-xl bg-[#F05D24] hover:bg-[#D84A15] text-white text-xs font-bold shadow-md transition-colors"
            >
              <RefreshCw className="w-4 h-4" />
              <span>Muat Ulang Halaman</span>
            </button>
          </div>
        </div>
      );
    }

    return this.props.children;
  }
}
